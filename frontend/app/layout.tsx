import type { Metadata } from "next";
import Link from "next/link";
import { requiredPublicUrl } from "@/lib/config";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: requiredPublicUrl("NEXT_PUBLIC_SITE_URL"),
  title: { default: "Fieldline Catalog", template: "%s | Fieldline Catalog" },
  description:
    "Commercial automation products powered by a headless WordPress catalog.",
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en">
      <body>
        <a className="skip-link" href="#main">
          Skip to content
        </a>
        <header className="site-header">
          <Link href="/products" className="brand">
            <span aria-hidden="true">F/</span> Fieldline
          </Link>
          <p>Automation catalog</p>
        </header>
        <main id="main">{children}</main>
        <footer>
          <p>Headless WordPress + Next.js reference implementation.</p>
        </footer>
      </body>
    </html>
  );
}
