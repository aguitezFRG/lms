const requiredEnvironment = [
  "CF_ACCESS_CLIENT_ID",
  "CF_ACCESS_CLIENT_SECRET",
  "CRON_SHARED_SECRET",
  "RENDER_RESET_URL",
  "RESET_HMAC_SECRET",
] as const;

function environment(name: (typeof requiredEnvironment)[number]): string {
  const value = Deno.env.get(name)?.trim();

  if (!value) {
    throw new Error(`Missing required environment variable: ${name}`);
  }

  return value;
}

function manilaDate(): string {
  return new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Manila",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(new Date());
}

async function hmacHex(message: string, secret: string): Promise<string> {
  const encoder = new TextEncoder();
  const key = await crypto.subtle.importKey(
    "raw",
    encoder.encode(secret),
    { name: "HMAC", hash: "SHA-256" },
    false,
    ["sign"],
  );
  const signature = await crypto.subtle.sign("HMAC", key, encoder.encode(message));

  return Array.from(new Uint8Array(signature))
    .map((byte) => byte.toString(16).padStart(2, "0"))
    .join("");
}

async function sha256(value: string): Promise<string> {
  const bytes = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(value));

  return Array.from(new Uint8Array(bytes))
    .map((byte) => byte.toString(16).padStart(2, "0"))
    .join("");
}

async function equalSecrets(left: string, right: string): Promise<boolean> {
  return (await sha256(left)) === (await sha256(right));
}

Deno.serve(async (request: Request): Promise<Response> => {
  try {
    for (const name of requiredEnvironment) {
      environment(name);
    }

    const authorization = request.headers.get("authorization") ?? "";
    const suppliedCronSecret = authorization.startsWith("Bearer ")
      ? authorization.slice(7)
      : "";

    if (!await equalSecrets(suppliedCronSecret, environment("CRON_SHARED_SECRET"))) {
      return Response.json({ ok: false, error: "Unauthorized" }, { status: 401 });
    }

    const idempotencyKey = manilaDate();
    const signature = await hmacHex(idempotencyKey, environment("RESET_HMAC_SECRET"));
    let lastError = "The reset service did not respond.";

    for (const delay of [0, 5_000, 15_000]) {
      if (delay > 0) {
        await new Promise((resolve) => setTimeout(resolve, delay));
      }

      try {
        const response = await fetch(environment("RENDER_RESET_URL"), {
          method: "POST",
          headers: {
            "CF-Access-Client-Id": environment("CF_ACCESS_CLIENT_ID"),
            "CF-Access-Client-Secret": environment("CF_ACCESS_CLIENT_SECRET"),
            "X-Demo-Reset-Key": idempotencyKey,
            "X-Demo-Reset-Signature": signature,
          },
          signal: AbortSignal.timeout(90_000),
        });
        const body = await response.text();

        if (response.ok) {
          return new Response(body, {
            status: 200,
            headers: { "content-type": "application/json" },
          });
        }

        lastError = `Reset returned HTTP ${response.status}: ${body.slice(0, 500)}`;
      } catch (error) {
        lastError = error instanceof Error ? error.message : String(error);
      }
    }

    return Response.json({ ok: false, error: lastError }, { status: 503 });
  } catch (error) {
    return Response.json(
      { ok: false, error: error instanceof Error ? error.message : String(error) },
      { status: 500 },
    );
  }
});
