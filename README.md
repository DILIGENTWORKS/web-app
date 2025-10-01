# DiligentWorks – Static Site (Solutions & Managed Services)

This is a lightweight static site for DiligentWorks and built from scratch with royalty‑free images.

- Single page `index.html`
- Responsive styles in `assets/css/styles.css`
- Minimal interactions in `assets/js/main.js`
- Contact form handler `contact.php` suitable for GoDaddy

## Structure

- `index.html`
- `assets/css/styles.css`
- `assets/js/main.js`
- `contact.php`

## Images
Royalty‑free images are loaded from Unsplash via direct links. You may replace them with your own images by editing `index.html` and swapping the `src` URLs.

## Local preview
You can open `index.html` directly in a browser. The contact form requires a PHP server to test email sending.

To test locally with PHP (optional):

```bash
php -S 127.0.0.1:8000
```

Then browse to http://127.0.0.1:8000

## Deploying on GoDaddy (cPanel Linux hosting)

1. Zip and upload all files to your hosting account’s `public_html/` using cPanel File Manager (or use FTP).
2. Ensure `index.html`, `assets/`, and `contact.php` are in `public_html/`.
3. In `contact.php`, update these settings:
   - `$to` – set to your recipient mailbox, e.g. `info@yourdomain.com`
   - `$from` – set to a mailbox on the same domain, e.g. `no-reply@yourdomain.com`
4. Email deliverability:
   - Create the `$from` mailbox in cPanel (if not already) and set a password.
   - In your DNS (GoDaddy), ensure MX records point to your mailbox provider.
   - Add/verify an SPF record that includes your hosting server. Typical example:
     `v=spf1 a mx include:secureserver.net -all`
   - Optionally add DKIM if available in your cPanel/mail service.
5. PHP version: set PHP 8.x in cPanel (Select PHP Version).
6. Test the contact form on the live site. You should receive emails at the `$to` address.

## How the Contact Form Works

- The form posts to `contact.php` using standard POST.
- `assets/js/main.js` enhances this with `fetch()` to submit via AJAX, showing success/error messages inline.
- Anti‑spam measures:
  - Honeypot field `website` (hidden from humans)
  - Basic timing check via a hidden timestamp `_form_ts` (rejects submissions under 3 seconds)
- Mail delivery uses PHP `mail()` with headers:
  - `From:` uses your domain mailbox (per GoDaddy requirement)
  - `Reply-To:` set to the user’s email so you can reply directly

If email isn’t received:

- Check your spam/junk folder.
- Verify `$to` and `$from` values in `contact.php`.
- Ensure your domain’s MX and SPF records are correct.
- Try sending to a different on‑domain mailbox.
- Review Email Deliverability in cPanel.

## Customization

- Update site copy in `index.html` to reflect your offerings.
- Swap colors in `assets/css/styles.css` by editing the `:root` variables.
- Replace images by changing the URLs in `index.html`.

## Credits

- Fonts: Inter via Google Fonts
- Photos: Unsplash
 
---

## Deploying to Google Cloud (App Engine Standard, PHP 8.2)

This project includes an `app.yaml` for App Engine Standard (`php82`). App Engine does not allow `mail()` to send outbound email; use SendGrid (recommended) via API.

### Prerequisites

- Install the Google Cloud SDK and authenticate:

```bash
gcloud init
gcloud auth login
```

- Create a project (or use an existing one) and set it as default:

```bash
gcloud projects create YOUR_PROJECT_ID --name="DiligentWorks"
gcloud config set project YOUR_PROJECT_ID
```

- Create an App Engine app (choose a region, e.g., `europe-west2` for London or `us-central`):

```bash
gcloud app create --region=europe-west2
```

### Configure environment variables

`app.yaml` exposes env vars you should customize before deploying:

```yaml
env_variables:
  APP_NAME: "DiligentWorks"
  TO_EMAIL: "sales@yourdomain.com"
  FROM_EMAIL: "no-reply@yourdomain.com"
  # SENDGRID_API_KEY: ""
```

Set `TO_EMAIL`, `FROM_EMAIL`, and, if using SendGrid, uncomment and set `SENDGRID_API_KEY`.

### SendGrid setup (recommended for email)

1. Create a SendGrid account, generate an API key (Mail Send – Full Access).
2. In `app.yaml`, set `SENDGRID_API_KEY` to that key.
3. Verify a sender identity in SendGrid (single sender or domain authentication).
4. Optionally, domain authenticate your domain for best deliverability (SPF/DKIM records).

The backend (`contact.php`) will automatically use SendGrid if `SENDGRID_API_KEY` is present; otherwise it falls back to `mail()` (which will not work on App Engine but may work on shared hosts like GoDaddy).

### Deploy

From the project directory containing `app.yaml`:

```bash
gcloud app deploy
```

Then open the site:

```bash
gcloud app browse
```

### Test contact form

- Ensure `TO_EMAIL` and `FROM_EMAIL` are set in `app.yaml`.
- Ensure `SENDGRID_API_KEY` is set if deploying to App Engine.
- Submit the form at `/#contact`. You should receive the email at `TO_EMAIL`.

### Notes

- Static assets are served via the handler in `app.yaml` under `/assets/` with long cache headers.
- The root `/` serves `index.html`.
- The endpoint `/contact.php` is routed to the PHP script.

