import Link from "next/link";
export default function NotFound() {
  return (
    <section className="state">
      <h1>Product not found</h1>
      <p>This product may have moved or is not publicly available.</p>
      <Link href="/products">Return to products</Link>
    </section>
  );
}
