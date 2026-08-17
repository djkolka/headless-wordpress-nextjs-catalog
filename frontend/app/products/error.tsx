"use client";
export default function ProductsError({
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <section className="state state--error" role="alert">
      <h1>Catalog unavailable</h1>
      <p>We could not load the product catalog. Please try again.</p>
      <button onClick={reset}>Try again</button>
    </section>
  );
}
