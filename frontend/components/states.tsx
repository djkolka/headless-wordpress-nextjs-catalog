import Link from "next/link";

export function EmptyState() {
  return (
    <section className="state" aria-live="polite">
      <h2>No products found</h2>
      <p>Try a broader search or clear one of the filters.</p>
      <Link href="/products">Clear all filters</Link>
    </section>
  );
}
export function CatalogSkeleton() {
  return (
    <div
      className="product-grid"
      aria-label="Loading products"
      aria-busy="true"
    >
      {Array.from({ length: 6 }, (_, index) => (
        <div className="skeleton" key={index} />
      ))}
    </div>
  );
}
