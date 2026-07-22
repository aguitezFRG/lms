import sys
from pathlib import Path

from playwright.sync_api import sync_playwright


ENGINE = sys.argv[1] if len(sys.argv) > 1 else "chromium"
BASE_URL = "http://127.0.0.1:4173"


with sync_playwright() as playwright:
    browser_type = getattr(playwright, ENGINE)
    browser = browser_type.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()
    context.on("serviceworker", lambda worker: worker.on("console", lambda message: print(f"worker[{message.type}]: {message.text}", flush=True)))
    page.on("console", lambda message: print(f"console[{message.type}]: {message.text}", flush=True))
    page.on("pageerror", lambda error: print(f"pageerror: {error}", flush=True))
    page.on("requestfailed", lambda request: print(f"requestfailed: {request.url} {request.failure}", flush=True))
    def log_request(request):
        if "/__php" not in request.url:
            return
        print(f"request: {request.method} {request.url}", flush=True)
        if "/livewire-" in request.url and request.method == "POST":
            print(f"request-headers: {request.headers}", flush=True)
            print(f"request-body: {(request.post_data or '')[:1000]}", flush=True)

    page.on("request", log_request)
    def log_response(response):
        if "/__php" not in response.url:
            return
        print(f"response: {response.status} {response.url}", flush=True)
        if response.status == 419:
            print(f"response-body: {response.text()}", flush=True)

    page.on("response", log_response)

    page.goto(BASE_URL, wait_until="domcontentloaded", timeout=120_000)
    try:
        page.frame_locator("#laravel").locator("body").wait_for(timeout=240_000)
        heading = page.frame_locator("#laravel").locator("h1").first
        heading.wait_for(timeout=240_000)
        print(f"engine={ENGINE}")
        print(f"heading={heading.inner_text()}")
        print(f"iframe_url={page.locator('#laravel').get_attribute('src')}")

        page.frame_locator("#laravel").get_by_role("button", name="Super Admin", exact=False).click()
        page.wait_for_timeout(10_000)
        print(f"frames={[(frame.name, frame.url) for frame in page.frames]}", flush=True)
        filament = page.frame_locator("#laravel").locator("[wire\\:snapshot]").first
        filament.wait_for(timeout=120_000)
        inner_frame = page.frames[-1]
        print(f"panel_url={inner_frame.url}")
        print(f"panel_title={inner_frame.title()}")

        inner_frame.goto(f"{BASE_URL}/__php/admin/users/create", wait_until="domcontentloaded", timeout=120_000)
        inner_frame.get_by_label("First Name", exact=False).fill("Browser")
        inner_frame.get_by_label("Last Name", exact=False).fill("Probe")
        inner_frame.get_by_label("Display Name (Full)", exact=False).fill("Browser Probe")
        inner_frame.get_by_label("Email", exact=False).fill("browser.probe@demo.invalid")
        inner_frame.get_by_role("button", name="Create", exact=True).click()
        inner_frame.get_by_text("Browser Probe", exact=True).first.wait_for(timeout=120_000)

        inner_frame.goto(inner_frame.url, wait_until="domcontentloaded", timeout=120_000)
        inner_frame.goto(f"{BASE_URL}/__php/demo/profiles", wait_until="domcontentloaded", timeout=120_000)
        inner_frame.get_by_role("button", name="Browser Probe", exact=False).wait_for(timeout=120_000)
        inner_frame.get_by_role("button", name="Carlos Santos", exact=False).click()
        switch_dialog = inner_frame.get_by_role("dialog")
        switch_dialog.wait_for(timeout=30_000)
        dialog_box = switch_dialog.bounding_box()
        viewport = inner_frame.locator("body").evaluate("() => ({ width: innerWidth, height: innerHeight })")
        assert dialog_box is not None
        assert abs(dialog_box["x"] + dialog_box["width"] / 2 - viewport["width"] / 2) < 2
        assert abs(dialog_box["y"] + dialog_box["height"] / 2 - viewport["height"] / 2) < 2
        inner_frame.get_by_role("button", name="Keep current profile").click()
        print("livewire_mutation_persisted=true")
        print("profile_switch_modal_centered=true")
        page.screenshot(path=f"/tmp/instat-browser-proof-{ENGINE}.png", full_page=True)
    except Exception:
        fatal = page.locator("#fatal-message")
        print(f"fatal={fatal.inner_text() if fatal.count() else 'unavailable'}")
        page.screenshot(path=f"/tmp/instat-browser-proof-{ENGINE}-failed.png", full_page=True)
        raise
    finally:
        context.close()
        browser.close()
