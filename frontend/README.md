# Next.js catalog frontend

Copy the root `.env.example` to `frontend/.env.local`, fill server-only credentials and secrets, then run `npm install` and `npm run dev`. The App Router uses server components by default, validates every WordPress response with Zod, and caches public API reads for five minutes with product tags.

Draft preview calls WordPress only from the server using an Application Password. Revalidation requests must carry the timestamped HMAC signature documented in the root README.
