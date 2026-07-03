# QSA Homepage SEO Migration Audit

Source audited: [https://quickseoanalysis.com/](https://quickseoanalysis.com/)
Audit date: 2026-07-03
Target Laravel app: homepage metadata and SEO preservation before UI redesign

## 1. HTML Meta

### Preserve from live homepage

- Title: `SEO Checker With Free Audit Report-Quick SEO Analysis`
- Meta Description: `Analyze your website with best SEO Optimizer. This tool or software generates free audit report along with SEO tips and reviews  to improve rankings on SERPs`
- Meta Keywords: `Seo Audit, Seo Checker, Seo Tools, Seo Optimizer, Seo Analyzer, Quick Seo Analysis, Free Audit Report,Quick SEO Analysis, Website SEO Checker`
- Robots: `index,archive,follow`
- Language meta: `en-US`
- Charset: `utf-8`
- Viewport: `width=device-width, initial-scale=1.0`
- Application name: `quickseoanalysis`
- Author: `quickseoanalysis`
- URL meta: `https://www.quickseoanalysis.com/`
- Additional title meta: `SEO Checker With Free Audit Report-Quick SEO Analysis`

### Missing on live homepage

- Canonical tag
- hreflang tags
- Open Graph tags
- Twitter Card tags
- Theme color
- Manifest

## 2. Social Meta

### Found on live homepage

- None

### Missing on live homepage

- `og:title`
- `og:description`
- `og:image`
- `og:url`
- `og:type`
- `twitter:card`
- `twitter:title`
- `twitter:description`
- `twitter:image`

## 3. Structured Data

### Found on live homepage

- No JSON-LD found
- No Organization schema
- No WebSite schema
- No SearchAction schema
- No SoftwareApplication schema
- No Breadcrumb schema
- No FAQ schema
- No Article schema on homepage
- No Review schema on homepage

## 4. Technical SEO

### Found on live homepage

- Canonical URL in meta `name="url"` only: `https://www.quickseoanalysis.com/`
- Favicon: `/favicon.png`
- Robots directive: `index,archive,follow`
- Language: `en`
- X-Frame-Options meta: `allow`

### Missing on live homepage

- Canonical link tag
- `hreflang`
- `theme-color`
- `manifest`
- alternate links

## 5. Homepage Content

### H1

- `SEO Analysis And Audit Report For Free`

### H2

- `Website Analysis With SEO Auditizer`
- `Use Mobile Friendliness As A Ranking Signal`
- `Social Media Presence That Delivers Quick`
- `Most Popular`

### H3

- `Get Started`
- `Which Techniques Provide Greater Value to SEO...`
- `Check Out An Ultimate Guide For SEO Audit To ...`
- `See How These Tips Could Help You To Boost Th...`
- `You’ll Be Amazed To Know The Reasons, Why You...`

### Hero copy / prominent copy

- `User-friendly SEO Checker Tool`
- `Find Your Website SEO Score Here`
- `Get Started`
- `Explore`
- `Quick Seo Analysis performs in-depth website analysis to provide an account of all the supporting and blocking SEO practices for your website. It generates a SEO score after evaluating the SEO approach you have been using for long.`

### Important keyword phrases currently reinforced

- SEO Checker
- Free Audit Report
- SEO Analysis
- Website SEO Score
- SEO Audit
- SEO Optimizer
- Website Analysis
- SEO Tools

## 6. Images

### Important images found on homepage

- Logo: `/images/logo.png` with alt `Quick Seo Analysis`
- Alternate/footer logo: `/images/logo-white.png` with alt `Quick Seo Analysis`
- Hero/support imagery: `/images/gif-web.gif`, `/images/img_2.jpg`, `/images/img_3.jpg`
- Favicon / site icon: `/favicon.png`

### Article image alts

- `Which Techniques Provide Greater Value to SEO?`
- `Check Out An Ultimate Guide For SEO Audit To Boost Your SEO Ranking!`
- `See How These Tips Could Help You To Boost The Conversion Rate Of Your Site!`
- `You’ll Be Amazed To Know The Reasons, Why Your Website Needs An SEO Audits !`

### og:image on live homepage

- Not present

## 7. Homepage URLs / Internal Links

### Internal links found on the live homepage

- `/`
- `/Account/Login`
- `#secoundSection`
- `/blog/which-techniques-provide-greater-value-to-seo`
- `/blog/check-out-an-ultimate-guide-for-seo-audit-to-boost-your-seo-ranking`
- `/blog/see-how-these-tips-could-help-you-to-boost-the-conversion-rate-of-your-site`
- `/blog/you-ll-be-amazed-to-know-the-reasons-why-your-website-needs-an-seo-audits`
- `/account/register`
- `/sitemap.xml`
- `/privacypolicy`
- `/termsconditions`
- `/Blog`
- `/Account/Register`
- `/Account/Login`

### External link found on the live homepage

- `https://www.virtuousreviews.com/`

## 8. Recommendations

### A. Preserve Exactly

These are the highest-value legacy signals to carry into the Laravel homepage:

- Page title
- Meta description
- Meta keywords
- Robots directive
- Core brand phrase `Quick SEO Analysis`
- Core intent phrases: `SEO Checker`, `Free Audit Report`, `Website SEO Score`

### B. Improve

Safe upgrades that should help rather than harm:

- Add a proper canonical tag
- Add Open Graph metadata
- Add Twitter Card metadata
- Add Organization, WebSite, and SoftwareApplication JSON-LD
- Add `hreflang` for `en` and `x-default`
- Add `theme-color`
- Add a dedicated social share image

### C. New Metadata for modern SEO / AI Search

Recommended additions:

- Organization schema
- WebSite schema
- SoftwareApplication schema
- Share image tuned for link previews
- Better OG/Twitter parity
- AI-friendly page summary in structured data and social descriptions

## 9. Migration Checklist

### Completed in Laravel implementation

- Preserve live title exactly
- Preserve live meta description exactly
- Preserve live keywords exactly
- Preserve live robots directive exactly
- Preserve live author/application-name/language signals
- Add canonical tag using app route
- Add Open Graph metadata
- Add Twitter Card metadata
- Add `en` and `x-default` alternates
- Add Organization schema
- Add WebSite schema
- Add SoftwareApplication schema
- Add share image asset

### Still worth reviewing before final homepage redesign

- Compare old and new internal link architecture
- Keep crawlable link paths to key conversion pages
- Review whether blog/internal content should be surfaced again on the new homepage
- Decide whether to keep or intentionally retire the old keyword meta long-term
- Review live domain canonical once the final production domain is confirmed

## 10. Laravel Files Updated for SEO Preservation

- `resources/views/components/layouts/app.blade.php`
- `public/images/social/home-share-card.svg`
- `docs/QSA_HOMEPAGE_SEO_MIGRATION.md`
