# Younimatiq Platform Living Audit

| Module | Page / Path | Issue | Severity | Fix |
|--------|-------------|-------|----------|-----|
| Student | `/student/dashboard` | Rewards/badges not passed to Inertia | High | Wired `rewards`, `badges`, `rewardStats` from controller |
| Student | `/student/courses/{id}/skill-tree` | React expected array; API returns `{nodes,...}` | High | Consume `treeData.nodes` + portfolio/badges tabs |
| Student | Nav | No activities/rewards hub | Medium | Added `/student/activities` and `/student/rewards` |
| Tutor training | `submitAssignment` | Auto-passed without HR | High | Status → `under_review`; HR queue + emails |
| Admin | HR reviews | Missing | High | `/admin/tutor-training/reviews` approve/reject/revision |
| Matching | Enroll | No configurable mode | High | Setting `tutor_match_mode` auto\|suggest\|manual + suggestions table |
| Matching | Skills admin | Category requirements UI missing | Medium | Category sync + match/HR settings on Skills page |
| Design | Portals | Inconsistent chrome | Medium | CSS portal tokens + `PageHeader`/`KpiCard`/etc. |
| Admin | Dashboard | No date filter on KPIs | Low | `from`/`to` query filters on revenue/enrollments |
| Tutor | Dashboard | Training/skills not surfaced | Medium | Training modules + pending review + verified skills |
| Public | CTAs/images | Prior Blade→React gaps | Medium | Existing LeadModal/CourseCard fixes retained; responsive shells |

## Smoke paths
- Admin login → dashboard date filter → HR reviews → Skills match settings
- Tutor login → training submit → waits for review
- Student login → dashboard rewards → activities → skill tree → portfolio

## E2E verified (mysql, 2026-11-04)
- `TutorTrainingService::submitAssignment` → status `under_review` (not auto-pass)
- `approveSubmission` → status `passed` with score
- Match mode default `suggest`
- Migration `2026_11_04_000001_hr_training_review_and_match_mode` applied
- Auth gates: `/admin/tutor-training/reviews`, `/student/activities`, `/student/rewards` redirect guests
- Frontend `npm run build` succeeded
