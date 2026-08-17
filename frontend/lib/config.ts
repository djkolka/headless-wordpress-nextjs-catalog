export function requiredPublicUrl(name: "NEXT_PUBLIC_SITE_URL"): URL {
  const value = process.env[name];
  if (!value) throw new Error(`${name} is not configured.`);

  try {
    return new URL(value);
  } catch {
    throw new Error(`${name} must be an absolute URL.`);
  }
}
