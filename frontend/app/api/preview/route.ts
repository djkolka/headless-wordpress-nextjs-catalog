import { timingSafeEqual } from "node:crypto";
import { draftMode } from "next/headers";
import { NextRequest, NextResponse } from "next/server";
import { fetchProduct } from "@/lib/wordpress";

function equal(left: string, right: string): boolean {
  const a = Buffer.from(left);
  const b = Buffer.from(right);
  return a.length === b.length && timingSafeEqual(a, b);
}
export async function GET(request: NextRequest) {
  const secret = request.nextUrl.searchParams.get("secret") ?? "";
  const expected = process.env.PREVIEW_SECRET ?? "";
  const slug = request.nextUrl.searchParams.get("slug") ?? "";
  if (!expected || !equal(secret, expected) || !/^[a-z0-9-]+$/.test(slug))
    return NextResponse.json(
      { code: "invalid_preview_request", message: "Invalid preview request." },
      { status: 401 },
    );
  try {
    await fetchProduct(slug, true);
  } catch {
    return NextResponse.json(
      {
        code: "preview_product_not_found",
        message: "Preview product not found or access denied.",
      },
      { status: 404 },
    );
  }
  (await draftMode()).enable();
  return NextResponse.redirect(new URL(`/products/${slug}`, request.url));
}
