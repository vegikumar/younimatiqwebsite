# Blade orphans (Inertia-canonical)

Do **not** expand these Blade templates for new features. Prefer the matching Inertia React pages. Keep Blade only until traffic/confirmation that React covers the flow, then remove.

| Blade | Prefer instead |
| --- | --- |
| `resources/views/booking/checkout.blade.php` | `resources/js/Pages/Public/Booking/Checkout.jsx` |
| `resources/views/custom-enrollment/checkout.blade.php` | `resources/js/Pages/Public/CustomEnrollment/Checkout.jsx` |
| Student/tutor/admin portal Blade dashboards (if any remain under `resources/views/`) | Matching `resources/js/Pages/{Role}/*` |

Canonical public checkout shells use `PublicLayout`. Authenticated student checkout may still use `DashboardLayout`.
