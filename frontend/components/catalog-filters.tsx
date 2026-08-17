import Link from "next/link";
import type { ProductQuery } from "@/lib/wordpress";

export function CatalogFilters({ query }: { query: ProductQuery }) {
  return (
    <form className="filters" action="/products" method="get" role="search">
      <div className="field field--wide">
        <label htmlFor="search">Search products</label>
        <input
          id="search"
          name="search"
          type="search"
          defaultValue={query.search}
          placeholder="Sensor, controller, SKU…"
        />
      </div>
      <div className="field">
        <label htmlFor="category">Category slug</label>
        <input
          id="category"
          name="category"
          defaultValue={query.category}
          placeholder="process-sensors"
        />
      </div>
      <div className="field">
        <label htmlFor="manufacturer">Manufacturer slug</label>
        <input
          id="manufacturer"
          name="manufacturer"
          defaultValue={query.manufacturer}
          placeholder="northstar-controls"
        />
      </div>
      <div className="field">
        <label htmlFor="application">Application slug</label>
        <input
          id="application"
          name="application"
          defaultValue={query.application}
          placeholder="water-treatment"
        />
      </div>
      <div className="field">
        <label htmlFor="sort">Sort by</label>
        <select id="sort" name="sort" defaultValue={query.sort ?? "date"}>
          <option value="date">Newest</option>
          <option value="title">Name</option>
          <option value="price">Price</option>
          <option value="modified">Recently updated</option>
        </select>
      </div>
      <div className="field">
        <label htmlFor="order">Order</label>
        <select id="order" name="order" defaultValue={query.order ?? "desc"}>
          <option value="desc">Descending</option>
          <option value="asc">Ascending</option>
        </select>
      </div>
      <div className="filter-actions">
        <button type="submit">Apply filters</button>
        <Link href="/products">Clear</Link>
      </div>
    </form>
  );
}
