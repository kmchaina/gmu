# NIMR GMU — Frontend Modernisation Plan
**Classification:** Internal Technical Roadmap  
**Date:** May 2026  
**Status:** Approved for Execution  

---

## 1. The Problem

The current system is built on AdminLTE 3 — a Bootstrap 4 admin template from 2018. It works, but it communicates the wrong thing. A system positioning itself as an internationally competitive Grant Management platform cannot look like a generic open-source dashboard. Every interaction — page reloads on form submit, jQuery-driven dropdowns, static table layouts — signals legacy technology to every international donor, auditor, or peer institution that opens it.

The backend is solid. Every model, migration, controller, service class, AI engine, scheduler, and audit trail built so far is correct and stays. The problem is entirely the presentation layer.

---

## 2. The Decision

**Stack: Laravel 12 + Inertia.js + React 18 + TypeScript + Tailwind CSS + shadcn/ui**

### Why this combination

| Technology | Role | Why |
|---|---|---|
| **Laravel 12** | Backend / API | All existing work stays. Zero backend rewrite. |
| **Inertia.js** | Bridge layer | Replaces Blade views. No separate API needed. Laravel controllers return Inertia responses instead of Blade views. |
| **React 18** | UI framework | Industry standard. Massive ecosystem. Strong TypeScript support. |
| **TypeScript** | Type safety | Catches errors at compile time. Essential for a system handling financial and compliance data. |
| **Tailwind CSS** | Styling | Utility-first. No opinionated components. Full design control. |
| **shadcn/ui** | Component library | Not a dependency — components are copied into your codebase. Built on Radix UI primitives. Accessible, composable, looks professional. |
| **Vite** | Build tool | Already in Laravel 12. Fast HMR. |
| **Tanstack Table** | Data tables | Powerful, headless table library for the complex grant data tables we need. |
| **Recharts** | Charts | Replaces CDN Chart.js. Proper React components for the analytics dashboard. |
| **React Hook Form + Zod** | Forms & validation | Type-safe forms with schema validation. Replaces raw HTML forms. |

### What this is NOT
- Not a full rewrite. The backend is untouched.
- Not a microservices split. Laravel remains the single application.
- Not a separate React app. Inertia keeps everything in one Laravel project.

---

## 3. What Stays (Backend — 100% preserved)

Everything built to date remains:

- All 20+ database migrations
- All Eloquent models with relationships, casts, traits
- All controllers (Awards, Proposals, Calls, Reports, Donors, AI, Analytics, etc.)
- The `LogsActivity` trait and full audit trail
- The `GmuAiService`, `GrantMatchingEngine`, `ComplianceRiskEngine`
- All Mail classes and Blade email templates
- The Laravel Scheduler and Artisan commands
- Authentication (Laravel Breeze — swapped to Inertia variant)
- Role-based middleware and Gates
- All routes in `web.php`

---

## 4. What Gets Replaced (Frontend — full rebuild)

- All `resources/views/*.blade.php` files (except email templates)
- AdminLTE package and its config
- jQuery and Bootstrap JavaScript
- The `adminlte.php` config sidebar system
- CDN-loaded libraries (Chart.js, Font Awesome via CDN)

---

## 5. Phased Execution Plan

### Phase 0 — Setup & Infrastructure
**Duration: 3–4 days**  
**Goal: Inertia + React running alongside existing Blade views**

#### Tasks
- [ ] Install Inertia server-side adapter: `composer require inertiajs/inertia-laravel`
- [ ] Install Inertia client-side: `npm install @inertiajs/react`
- [ ] Install React 18 + TypeScript: `npm install react react-dom @types/react @types/react-dom typescript`
- [ ] Install Tailwind CSS: `npm install tailwindcss @tailwindcss/forms @tailwindcss/typography`
- [ ] Install shadcn/ui CLI and initialise
- [ ] Install supporting libraries: `npm install @tanstack/react-table recharts react-hook-form zod @hookform/resolvers`
- [ ] Configure `vite.config.ts` for React + Inertia
- [ ] Create root Inertia layout (`resources/js/app.tsx`, `resources/js/Pages/`)
- [ ] Create the shared `AppLayout` component (sidebar, topbar, notifications)
- [ ] Configure `HandleInertiaRequests` middleware (shared data: auth user, role, unread notifications count)
- [ ] Set up TypeScript path aliases
- [ ] Run both systems in parallel — Blade still serves existing routes

#### Deliverable
A working Inertia + React shell that can render one test page. Existing Blade routes untouched.

---

### Phase 1 — Design System & Core Components
**Duration: 1 week**  
**Goal: Build the component library before building pages**

This phase is critical. Every page will use these components. Getting them right once means consistency everywhere.

#### Components to build

**Layout**
- `AppLayout` — sidebar navigation, topbar, breadcrumbs, flash messages
- `PageHeader` — consistent page title + action button pattern
- `Sidebar` — role-aware navigation links (replaces adminlte.php config)

**Data Display**
- `DataTable` — sortable, filterable, paginated (Tanstack Table)
- `StatCard` — KPI metric cards (used on dashboard and analytics)
- `TrafficLight` — green/yellow/red indicator with tooltip breakdown
- `Badge` — status badges with consistent colour mapping
- `ProgressBar` — budget utilisation bars
- `Timeline` — activity log display

**Forms**
- `TextInput`, `SelectInput`, `DateInput`, `Textarea` — all with error states
- `FileUpload` — with drag-and-drop and preview
- `RepeaterField` — dynamic add/remove rows (qualifications, publications)
- `FormSection` — card-wrapped form sections with title

**Feedback**
- `Alert` — success/error/warning/info
- `Modal` — confirmation dialogs (replace browser confirm())
- `Toast` — flash message system
- `EmptyState` — consistent empty list messaging

**Charts (Recharts wrappers)**
- `DonutChart`, `BarChart`, `LineChart` — typed wrappers around Recharts

#### Deliverable
A Storybook-style component showcase page at `/dev/components` (development only). Every component rendered with all its states.

---

### Phase 2 — Authentication & Shell
**Duration: 2 days**  
**Goal: Login page + authenticated shell working end-to-end**

- [ ] Replace `resources/views/auth/login.blade.php` with Inertia page
- [ ] Design: full-screen split layout — NIMR branding left, form right
- [ ] Dark navy (`#003366`) brand colour as primary
- [ ] NIMR logo / microscope icon, institution name, tagline
- [ ] Swap Laravel Breeze to Inertia variant for auth routes
- [ ] Build the authenticated `AppLayout` with:
  - Collapsible sidebar with role-based menu
  - Topbar: page title, notification bell (live count), user avatar dropdown
  - Role badge visible on user menu
  - "My Profile" link to own researcher profile
- [ ] Implement flash message system (success/error toasts on redirect)

#### Deliverable
Login → Dashboard navigation working. Shell looks finished.

---

### Phase 3 — Dashboard
**Duration: 2 days**  
**Goal: Replace the dashboard with a real-time overview**

- [ ] KPI stat cards: active awards, open calls, overdue reports, proposals pending review
- [ ] Traffic light summary: portfolio health at a glance
- [ ] Upcoming deadlines list (next 14 days, sorted)
- [ ] Recent activity feed (last 10 audit log entries)
- [ ] Role-aware rendering — PI sees only their grants; coordinators see full portfolio
- [ ] Quick action buttons: "New Call", "New Proposal", "Log Report"

---

### Phase 4 — Funding Calls Module
**Duration: 3 days**

- [ ] `calls/Index` — filterable table (status, donor, deadline), closing-soon highlight
- [ ] `calls/Show` — call details, interest expressions list, GMU team selection panel
- [ ] `calls/Create` + `calls/Edit` — full form with file upload for call document
- [ ] Interest expression flow (researcher clicks "Express Interest" inline)
- [ ] Team selection flow (coordinator selects team from interest list)

---

### Phase 5 — Proposals Module
**Duration: 4 days**

- [ ] `proposals/Index` — kanban-style pipeline view (columns by status) + fallback table view
- [ ] `proposals/Create` — multi-step form: details → team → documents → submit
- [ ] `proposals/Show` — full proposal view with review panel per role
- [ ] `proposals/Edit` — pre-populated form
- [ ] Internal review workflow UI — three review panels (technical/compliance/budget), status auto-advance indicator

---

### Phase 6 — Awards Module
**Duration: 5 days** *(most complex page in the system)*

- [ ] `awards/Index` — card grid with traffic light per award, filter by status/donor
- [ ] `awards/Create` + `awards/Edit`
- [ ] `awards/Show` — the flagship page:
  - Award header card (contract number, PI, period, traffic light)
  - Tab navigation: Budget | Reports | Compliance | Ethics | Monitoring
  - Each tab: data table + inline add form (no page reload — Inertia partial reloads)
  - Budget tab: progress bars, burn rate chart, category breakdown
  - Traffic light card with live breakdown
  - Right panel: quick stats, days remaining, recent activity

---

### Phase 7 — Reports Module
**Duration: 2 days**

- [ ] `reports/Index` — filterable by status, award, type; overdue highlighted
- [ ] `reports/Create` — report submission form with file upload
- [ ] `reports/Show` — report detail, GMU review panel, approve/reject actions
- [ ] `reports/Edit`

---

### Phase 8 — Analytics Dashboard
**Duration: 3 days**

- [ ] Recharts-powered charts: portfolio donut, proposals pipeline, awards per year, funding by donor
- [ ] Budget utilisation table with visual progress bars
- [ ] Report timeliness donut
- [ ] KPI cards with trend indicators (up/down vs last period)
- [ ] Date range filter (this year / last year / all time)
- [ ] Export to PDF (browser print stylesheet)

---

### Phase 9 — Researcher Profiles & FCOI
**Duration: 3 days**

- [ ] `researchers/Index` — profile card grid, searchable by name/expertise
- [ ] `researchers/Show` — full profile: bio, expertise tags, grant history timeline, publications list, FCOI status
- [ ] `researchers/Edit` — profile editor with repeater fields (qualifications, publications)
- [ ] FCOI disclosure form and review workflow
- [ ] ORCID ID field with validation (ready for OAuth activation)

---

### Phase 10 — Proposal Library
**Duration: 2 days**

- [ ] `library/Index` — card grid with category filter + full-text search
- [ ] `library/Show` — content viewer with "Copy to clipboard" button, usage count
- [ ] `library/Create` + `library/Edit` — rich text editor (TipTap) for content
- [ ] Coordinator approval workflow inline

---

### Phase 11 — Document Repository & Donor Policies
**Duration: 2 days**

- [ ] `documents/Index`, `documents/Create`, `documents/Edit`
- [ ] `donor-policies/Index`, `donor-policies/Create`, `donor-policies/Edit`
- [ ] File upload with preview, download link

---

### Phase 12 — AI Intelligence Hub
**Duration: 3 days**

- [ ] `ai/Hub` — feature overview page, LLM provider status indicator
- [ ] `ai/Matching` — call list; click a call to see researcher match scores with breakdown
- [ ] `ai/MatchingResults` — ranked researcher cards with match score bars and rationale
- [ ] `ai/Risk` — award list; click to run risk analysis
- [ ] `ai/RiskResult` — risk score card, breakdown by dimension (financial/compliance/ethics/reporting), recommended actions
- [ ] Report quality checker — inline in `reports/Show`, real-time feedback
- [ ] Proposal section drafter — AI writing panel in `proposals/Create`

---

### Phase 13 — Admin & Audit Log
**Duration: 2 days**

- [ ] `admin/users/Index`, `Create`, `Show`, `Edit`
- [ ] `admin/activity-log/Index` — filterable audit log with model/user/action/date filters
- [ ] `admin/activity-log/Show` — field-level diff view (before/after in two columns)
- [ ] Notifications centre (`notifications/Index`)

---

### Phase 14 — Polish, Accessibility & Cutover
**Duration: 1 week**

- [ ] Keyboard navigation audit across all pages
- [ ] ARIA labels on all interactive elements
- [ ] Mobile responsiveness pass (priority: dashboard, awards show, proposals pipeline)
- [ ] Loading states on all async operations
- [ ] Error boundary components (graceful failure pages)
- [ ] Empty state illustrations for all list pages
- [ ] Remove AdminLTE package and all Blade view files
- [ ] Remove jQuery
- [ ] Final route audit
- [ ] Production build and asset optimisation (`npm run build`)
- [ ] Cross-browser test (Chrome, Firefox, Edge)

---

## 6. Timeline Summary

| Phase | Work | Duration |
|---|---|---|
| 0 | Setup & Infrastructure | 3–4 days |
| 1 | Design System & Components | 1 week |
| 2 | Auth & Shell | 2 days |
| 3 | Dashboard | 2 days |
| 4 | Funding Calls | 3 days |
| 5 | Proposals | 4 days |
| 6 | Awards (flagship) | 5 days |
| 7 | Reports | 2 days |
| 8 | Analytics | 3 days |
| 9 | Researcher Profiles | 3 days |
| 10 | Proposal Library | 2 days |
| 11 | Documents & Policies | 2 days |
| 12 | AI Hub | 3 days |
| 13 | Admin & Audit | 2 days |
| 14 | Polish & Cutover | 1 week |
| **Total** | | **~8 weeks** |

*Assumes one focused developer. With two developers working parallel phases: 5–6 weeks.*

---

## 7. Design Direction

### Colour System
```
Primary:      #003366  (NIMR navy)
Primary light:#0055a5
Accent:       #00A86B  (research green)
Warning:      #F59E0B
Danger:       #EF4444
Success:      #10B981
Background:   #F8FAFC
Surface:      #FFFFFF
Border:       #E2E8F0
Text primary: #0F172A
Text muted:   #64748B
```

### Typography
- **Inter** (Google Fonts) — clean, modern, highly legible at all sizes
- Heading scale: 2xl / xl / lg / base / sm
- Monospace: **JetBrains Mono** for contract numbers, IDs, audit log values

### Layout Principles
- Sidebar: 260px fixed, collapsible to icon-only on mobile
- Content max-width: 1400px centred
- Cards: subtle shadow, 8px radius, 1px border
- Tables: sticky header, alternating row subtle tint, hover highlight
- Forms: grouped in `FormSection` cards, clear field labels above (not inside) inputs
- Spacing: 8px grid throughout

### Traffic Light (Visual Redesign)
Not a badge — a prominent circular indicator with pulsing animation on red. Visible from a distance on the award card. Breakdown tooltip on hover.

---

## 8. What This Delivers

When complete, the NIMR GMU system will:

1. **Look credible** to international donors — indistinguishable from commercial SaaS GMS platforms
2. **Feel fast** — no full page reloads, instant navigation, optimistic UI updates
3. **Work on any device** — responsive from 320px mobile to 4K displays
4. **Be maintainable** — typed components, consistent design system, one source of truth per component
5. **Be extensible** — adding a new module means adding a new set of React pages. The pattern is established.
6. **Be accessible** — Radix UI primitives (via shadcn/ui) are WCAG 2.1 AA compliant by design

---

## 9. Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Inertia + React learning curve | Medium | Medium | Start with simple pages (dashboard, calls index) before complex ones (awards show) |
| Blade and Inertia routes conflict | Low | High | Use `HandleInertiaRequests` middleware cleanly; keep Inertia pages in separate directory |
| TypeScript strictness slowing progress | Medium | Low | Use `strict: false` initially, tighten after cutover |
| TipTap rich text editor complexity | Low | Low | Plain textarea fallback for proposal library if TipTap integration takes too long |
| Phase 6 (Awards Show) scope creep | High | Medium | Build as tabbed layout first, enhance tabs individually |

---

## 10. Immediate Next Steps

1. Confirm stack decision (Inertia + React)
2. Begin Phase 0 installation
3. Design the `AppLayout` component before any page — sidebar structure determines everything else
4. Establish colour tokens in `tailwind.config.ts` before writing any UI

---

*Document prepared by: GMU Technical Team*  
*Next review: After Phase 1 (Design System) completion*
