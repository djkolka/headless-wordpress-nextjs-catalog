import { createHmac, timingSafeEqual } from "node:crypto";
import { revalidatePath, revalidateTag } from "next/cache";
import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";

const payloadSchema = z.object({
  tags: z.array(z.string().regex(/^[a-z0-9:/-]+$/)).max(20),
  paths: z.array(z.string().regex(/^\/products(?:\/[a-z0-9-]+)?$/)).max(20),
});
function safeEqual(left: string, right: string): boolean {
  const a = Buffer.from(left, "hex");
  const b = Buffer.from(right, "hex");
  return a.length > 0 && a.length === b.length && timingSafeEqual(a, b);
}
export async function POST(request: NextRequest) {
  const secret = process.env.REVALIDATION_SECRET;
  const timestamp = request.headers.get("x-catalog-timestamp") ?? "";
  const signature = request.headers.get("x-catalog-signature") ?? "";
  const body = await request.text();
  const seconds = Number(timestamp);
  if (
    !secret ||
    !Number.isInteger(seconds) ||
    Math.abs(Date.now() / 1000 - seconds) > 300
  )
    return NextResponse.json(
      { code: "invalid_signature", message: "Invalid or expired signature." },
      { status: 401 },
    );
  const expected = createHmac("sha256", secret)
    .update(`${timestamp}.${body}`)
    .digest("hex");
  if (!safeEqual(signature, expected))
    return NextResponse.json(
      { code: "invalid_signature", message: "Invalid or expired signature." },
      { status: 401 },
    );
  let payload: unknown;
  try {
    payload = JSON.parse(body);
  } catch {
    return NextResponse.json(
      { code: "invalid_payload", message: "Invalid revalidation payload." },
      { status: 400 },
    );
  }
  const parsed = payloadSchema.safeParse(payload);
  if (!parsed.success)
    return NextResponse.json(
      { code: "invalid_payload", message: "Invalid revalidation payload." },
      { status: 400 },
    );
  parsed.data.tags.forEach((tag) => revalidateTag(tag, "max"));
  parsed.data.paths.forEach((path) => revalidatePath(path));
  return NextResponse.json({
    revalidated: true,
    tags: parsed.data.tags,
    paths: parsed.data.paths,
  });
}
