import os
import sys
from urllib.parse import quote, urlparse

from playwright.sync_api import sync_playwright


ENGINE = sys.argv[1] if len(sys.argv) > 1 else "chromium"
BASE_URL = os.environ.get("BROWSER_PROOF_BASE_URL", "http://127.0.0.1:4173").rstrip("/")
PHP_BASE_URL = f"{BASE_URL}/__php"
PROFILE_SWITCH_COUNT = int(os.environ.get("BROWSER_PROOF_PROFILE_SWITCH_COUNT", "20"))
SEEDED_DIGITAL_DOCUMENTS = [
    ("33333333-3333-3333-3333-000000000001", "10.5117_9789462985100_previewpdf.pdf"),
    ("33333333-3333-3333-3333-000000000002", "292-Article Text-1045-1-10-20210812.pdf"),
    ("33333333-3333-3333-3333-000000000003", "An_Insight_in_Statistical_Techniques_and.pdf"),
    ("33333333-3333-3333-3333-000000000004", "1-s2.0-S2211379720321136-main.pdf"),
    ("33333333-3333-3333-3333-000000000005", "s12874-021-01432-5.pdf"),
    ("33333333-3333-3333-3333-000000000006", "Rice_Yield_Modeling_Using_Machine_Learni.pdf"),
]


def is_php_request(url):
    return "/__php" in url


def is_livewire_post(request):
    return (
        request.method == "POST"
        and "/livewire-" in request.url
        and is_php_request(request.url)
    )


def is_call_mounted_action(request):
    return is_livewire_post(request) and '"method":"callMountedAction"' in (request.post_data or "")


def is_profile_post(request):
    return (
        request.method == "POST"
        and urlparse(request.url).path.rstrip("/") == "/__php/demo/profiles"
    )


with sync_playwright() as playwright:
    browser_type = getattr(playwright, ENGINE)
    browser = browser_type.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    call_mounted_action_requests = []
    profile_post_requests = []
    document_requests = []
    unexpected_console_messages = []
    failed_requests = []
    bad_responses = []
    unused_preload_warnings = []

    def record_console(message, source="page"):
        formatted = f"{source}[{message.type}]: {message.text}"
        print(formatted, flush=True)
        lowered = message.text.lower()
        if "preload" in lowered and ("not used" in lowered or "unused" in lowered):
            unused_preload_warnings.append(formatted)
        if message.type == "error":
            unexpected_console_messages.append(formatted)

    def record_request(request):
        if request.resource_type == "document":
            document_requests.append(request)
        if is_call_mounted_action(request):
            call_mounted_action_requests.append(request)
        if is_profile_post(request):
            profile_post_requests.append(request)

        if not is_php_request(request.url):
            return
        print(f"request: {request.method} {request.url}", flush=True)
        if is_livewire_post(request):
            print(f"request-headers: {request.headers}", flush=True)
            print(f"request-body: {(request.post_data or '')[:1000]}", flush=True)

    def record_response(response):
        if response.status == 404 or response.status >= 500:
            bad_responses.append(response)
        if not is_php_request(response.url):
            return
        print(f"response: {response.status} {response.url}", flush=True)
        if response.status == 419 or response.status >= 500:
            print(f"response-body: {response.text()[:5000]}", flush=True)

    def record_request_failure(request):
        failure = f"{request.method} {request.url} {request.failure}"
        failed_requests.append(failure)
        print(f"requestfailed: {failure}", flush=True)

    context.on(
        "serviceworker",
        lambda worker: worker.on(
            "console", lambda message: record_console(message, source="worker")
        ),
    )
    page.on("console", record_console)
    page.on("pageerror", lambda error: unexpected_console_messages.append(f"pageerror: {error}"))
    page.on("pageerror", lambda error: print(f"pageerror: {error}", flush=True))
    page.on("request", record_request)
    page.on("requestfailed", record_request_failure)
    page.on("response", record_response)

    def assert_no_error_page(frame):
        body_text = frame.locator("body").inner_text(timeout=30_000)
        assert "No input file" not in body_text, body_text[:1000]
        assert "Error while loading page" not in body_text, body_text[:1000]

    def switch_profile(frame, profile_name, rapid_confirm=False):
        before_post_count = len(profile_post_requests)
        frame.goto(
            f"{PHP_BASE_URL}/demo/profiles",
            wait_until="domcontentloaded",
            timeout=120_000,
        )
        profile_button = frame.get_by_role("button", name=profile_name, exact=False)
        profile_button.wait_for(timeout=120_000)
        profile_button.click()
        dialog = frame.get_by_role("dialog")
        dialog.wait_for(timeout=30_000)
        confirm = frame.get_by_role("button", name="Switch profile")

        if rapid_confirm:
            confirm.evaluate("button => { for (let index = 0; index < 8; index++) button.click(); }")
        else:
            confirm.click()

        frame.locator("[wire\\:snapshot]").first.wait_for(timeout=120_000)
        assert_no_error_page(frame)
        assert len(profile_post_requests) == before_post_count + 1, (
            f"Expected one profile POST for {profile_name}, got "
            f"{len(profile_post_requests) - before_post_count}"
        )

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

        # Preserve the existing native Livewire mutation and persistence proof.
        inner_frame.goto(
            f"{PHP_BASE_URL}/admin/users/create",
            wait_until="domcontentloaded",
            timeout=120_000,
        )
        inner_frame.get_by_label("First Name", exact=False).fill("Browser")
        inner_frame.get_by_label("Last Name", exact=False).fill("Probe")
        inner_frame.get_by_label("Display Name (Full)", exact=False).fill("Browser Probe")
        inner_frame.get_by_label("Email", exact=False).fill("browser.probe@demo.invalid")
        inner_frame.get_by_role("button", name="Create", exact=True).click()
        inner_frame.get_by_text("Browser Probe", exact=True).first.wait_for(timeout=120_000)
        assert_no_error_page(inner_frame)

        inner_frame.goto(inner_frame.url, wait_until="domcontentloaded", timeout=120_000)
        inner_frame.goto(
            f"{PHP_BASE_URL}/demo/profiles",
            wait_until="domcontentloaded",
            timeout=120_000,
        )
        inner_frame.get_by_role("button", name="Browser Probe", exact=False).wait_for(timeout=120_000)
        inner_frame.get_by_role("button", name="Carlos Santos", exact=False).click()
        switch_dialog = inner_frame.get_by_role("dialog")
        switch_dialog.wait_for(timeout=30_000)
        dialog_box = switch_dialog.bounding_box()
        viewport = inner_frame.locator("body").evaluate(
            "() => ({ width: innerWidth, height: innerHeight })"
        )
        assert dialog_box is not None
        assert abs(dialog_box["x"] + dialog_box["width"] / 2 - viewport["width"] / 2) < 2
        assert abs(dialog_box["y"] + dialog_box["height"] / 2 - viewport["height"] / 2) < 2
        inner_frame.get_by_role("button", name="Keep current profile").click()
        print("livewire_mutation_persisted=true")
        print("profile_switch_modal_centered=true")

        # Establish the student profile used by the catalog interaction proof.
        switch_profile(inner_frame, "Carlos Santos")
        catalog_list_url = f"{PHP_BASE_URL}/app/user/catalogs"
        inner_frame.goto(catalog_list_url, wait_until="domcontentloaded", timeout=120_000)
        inner_frame.locator("[wire\\:snapshot]").first.wait_for(timeout=120_000)

        # Filter and sort controls must react to their first click.
        filter_button = inner_frame.get_by_role("button", name="Filter", exact=True)
        assert inner_frame.locator(".rr-filter-panel").count() == 0
        filter_button.click()
        inner_frame.locator(".rr-filter-panel").wait_for(timeout=120_000)
        print("catalog_filter_first_click=true")

        descending = inner_frame.get_by_role("button", name="Desc", exact=True)
        descending.click()
        ascending = inner_frame.get_by_role("button", name="Asc", exact=True)
        ascending.wait_for(timeout=120_000)
        ascending.click()
        descending.wait_for(timeout=120_000)
        print("catalog_sort_first_click_ascending=true")
        print("catalog_sort_first_click_descending=true")

        # One confirmation creates one event, closes the modal, renders one
        # immediate demo toast, and disables the action without navigation.
        catalog_url = f"{PHP_BASE_URL}/app/user/catalogs/22222222-2222-2222-2222-000000000003"
        inner_frame.goto(catalog_url, wait_until="domcontentloaded", timeout=120_000)
        request_button = inner_frame.get_by_role("button", name="Request Digital Copy", exact=True)
        request_button.click()
        request_dialog = inner_frame.locator(
            '[data-fi-modal-id*="-action-"].fi-modal-open'
        ).last
        request_modal_window = request_dialog.locator(".fi-modal-window")
        request_modal_window.wait_for(timeout=30_000)

        calls_before = len(call_mounted_action_requests)
        documents_before = len(document_requests)
        mutation_url = inner_frame.url
        submit_button = inner_frame.get_by_role("button", name="Submit Request", exact=True)
        with page.expect_response(
            lambda response: is_call_mounted_action(response.request),
            timeout=120_000,
        ) as mutation_response_info:
            submit_button.click()
        mutation_response = mutation_response_info.value
        assert mutation_response.status == 200

        toast = inner_frame.locator(".demo-notification")
        toast.wait_for(timeout=5_000)
        request_modal_window.wait_for(state="hidden", timeout=5_000)
        request_button.wait_for(state="visible", timeout=5_000)
        assert request_button.is_disabled()
        assert inner_frame.url == mutation_url
        assert len(call_mounted_action_requests) == calls_before + 1
        assert toast.count() == 1
        assert toast.locator(".demo-notification-title").inner_text() == "Digital request submitted!"
        assert not any(
            request.url == catalog_url
            for request in document_requests[documents_before:]
        ), "Catalog request unexpectedly reloaded the document"
        assert_no_error_page(inner_frame)
        print("catalog_call_mounted_action_once=true")
        print("catalog_success_feedback_immediate_and_singular=true")
        print("catalog_modal_closed=true")
        print("catalog_request_disabled_without_reload=true")

        # Revisit once to preserve the original persistence assertion.
        inner_frame.goto(catalog_url, wait_until="domcontentloaded", timeout=120_000)
        assert inner_frame.get_by_role(
            "button", name="Request Digital Copy", exact=True
        ).is_disabled()
        print("catalog_request_mutation_persisted=true")

        # Alternate student/admin twenty times. Rapid confirmation clicks on
        # every switch exercise the page-level single-flight guard.
        for switch_index in range(PROFILE_SWITCH_COUNT):
            profile_name = "Super Admin" if switch_index % 2 == 0 else "Carlos Santos"
            switch_profile(inner_frame, profile_name, rapid_confirm=True)
            print(
                f"profile_switch_{switch_index + 1:02d}={profile_name.replace(' ', '_').lower()}",
                flush=True,
            )
        print(f"profile_switches_single_flight={PROFILE_SWITCH_COUNT}")

        # End as an administrator, then load every seeded viewer and require
        # its exact packaged PDF response to be successful and correctly typed.
        switch_profile(inner_frame, "Super Admin", rapid_confirm=True)
        for material_id, filename in SEEDED_DIGITAL_DOCUMENTS:
            encoded_filename = quote(filename, safe="")
            expected_pdf_url = f"{BASE_URL}/pdfs/{encoded_filename}"
            viewer_url = f"{PHP_BASE_URL}/materials/{material_id}/viewer"
            with page.expect_response(
                lambda response, expected=expected_pdf_url: response.url == expected,
                timeout=30_000,
            ) as pdf_response_info:
                viewer_response = inner_frame.goto(
                    viewer_url,
                    wait_until="domcontentloaded",
                    timeout=120_000,
                )
            assert viewer_response is not None and viewer_response.status == 200
            pdf_response = pdf_response_info.value
            assert pdf_response.status == 200, f"{filename}: HTTP {pdf_response.status}"
            content_type = pdf_response.headers.get("content-type", "").split(";", 1)[0].lower()
            assert content_type == "application/pdf", f"{filename}: {content_type}"
            inner_frame.locator("canvas.pdf-canvas").first.wait_for(timeout=120_000)
            assert_no_error_page(inner_frame)
            print(f"seeded_pdf_ok={filename}", flush=True)
        print(f"seeded_digital_pdfs_opened={len(SEEDED_DIGITAL_DOCUMENTS)}")

        # Evaluate global browser hygiene only after all interactions complete.
        assert unused_preload_warnings == [], unused_preload_warnings
        assert unexpected_console_messages == [], unexpected_console_messages
        assert failed_requests == [], failed_requests
        assert bad_responses == [], [
            f"{response.status} {response.url}" for response in bad_responses
        ]
        print("unused_preload_warnings=0")
        print("unexpected_console_errors=0")
        print("unexpected_network_errors=0")
        page.screenshot(path=f"/tmp/instat-browser-proof-{ENGINE}.png", full_page=True)
    except Exception:
        fatal = page.locator("#fatal-message")
        print(f"fatal={fatal.inner_text() if fatal.count() else 'unavailable'}")
        print(f"unused_preload_warnings={unused_preload_warnings}", flush=True)
        print(f"unexpected_console_messages={unexpected_console_messages}", flush=True)
        print(f"failed_requests={failed_requests}", flush=True)
        print(
            f"bad_responses={[(response.status, response.url) for response in bad_responses]}",
            flush=True,
        )
        page.screenshot(path=f"/tmp/instat-browser-proof-{ENGINE}-failed.png", full_page=True)
        raise
    finally:
        context.close()
        browser.close()
