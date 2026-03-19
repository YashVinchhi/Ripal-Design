# Service Pricing, Cost Breakdown, and Unit Economics (Gujarat, India)

Date: March 16, 2026
Currency: INR

## 1) Purpose
This document gives a practical pricing and expense model for offering this platform as a managed service to architecture firms in Gujarat.

It includes:
- What you can charge per tier
- What you will spend per tier (hosting, domain, tools, support)
- Income vs expenditure for each tier
- First-year economics including setup fee
- Recommended minimum price for healthy margins

## 2) Key Assumptions (Important)
These numbers are realistic planning estimates, not fixed invoices.
Actual cost changes by client load, data usage, support burden, and your vendor choices.

Assumptions used:
- One client firm = one paying account
- Mostly single-tenant deployment per firm (recommended for architecture firms)
- Standard India cloud/VPS + MySQL stack
- SSL via free certificates (Lets Encrypt), so SSL cost is treated as 0
- Payment gateway MDR is excluded from core SaaS cost (pass-through to client or billed separately)
- GST is excluded from revenue and expense tables (show GST separately on invoices)

## 3) Tier Pricing (Recommended)

### Foundation Tier
- Setup fee (one-time): Rs 50,000
- Monthly recurring: Rs 10,000
- Typical client size: up to 15 users, up to 20 active projects

### Professional Tier
- Setup fee (one-time): Rs 1,20,000
- Monthly recurring: Rs 25,000
- Typical client size: up to 50 users, up to 75 active projects

### Studio Enterprise Tier
- Setup fee (one-time): Rs 3,00,000+
- Monthly recurring: Rs 65,000
- Typical client size: 100+ users, multi-office workflows

## 4) Common Cost Heads You Must Budget
1. Domain
2. Hosting/compute
3. Database and backups
4. Storage and bandwidth/CDN
5. Transactional email/SMS/WhatsApp (if included)
6. Monitoring and uptime tooling
7. Security tooling and patching effort
8. Support and account management labor
9. Compliance/admin overhead (billing, meetings, reporting)
10. Contingency reserve

## 5) Monthly Expenditure by Tier (Detailed)

### A) Foundation Tier (Monthly Expense)
Income:
- Monthly fee: Rs 10,000

Expenditure:
- Domain allocation (annual domain spread monthly): Rs 100
- Hosting (small VPS/cloud instance): Rs 1,400
- Database + scheduled backups: Rs 500
- File storage + transfer buffer: Rs 300
- Transactional email: Rs 300
- Monitoring/logging basic stack: Rs 200
- Tools/licenses allocation: Rs 300
- Included support labor (about 2 hours): Rs 2,500
- Admin/billing/comms overhead: Rs 700

Total monthly expenditure: Rs 6,300
Monthly operating profit: Rs 3,700
Operating margin: 37%

First-year (including setup):
- Revenue: Rs 50,000 + (Rs 10,000 x 12) = Rs 1,70,000
- Estimated onboarding/implementation cost: Rs 25,000
- Yearly recurring cost: Rs 6,300 x 12 = Rs 75,600
- Total first-year cost: Rs 1,00,600
- First-year gross profit: Rs 69,400

### B) Professional Tier (Monthly Expense)
Income:
- Monthly fee: Rs 25,000

Expenditure:
- Domain allocation: Rs 100
- Hosting (mid cloud instance): Rs 3,200
- Database + backup retention: Rs 1,200
- Storage + bandwidth/CDN: Rs 800
- Transactional email/SMS base: Rs 700
- Monitoring + alerting tools: Rs 500
- Tools/licenses allocation: Rs 700
- Included support labor (about 8 hours): Rs 7,100
- Account review + admin overhead: Rs 1,200

Total monthly expenditure: Rs 15,500
Monthly operating profit: Rs 9,500
Operating margin: 38%

First-year (including setup):
- Revenue: Rs 1,20,000 + (Rs 25,000 x 12) = Rs 4,20,000
- Estimated onboarding/implementation cost: Rs 55,000
- Yearly recurring cost: Rs 15,500 x 12 = Rs 1,86,000
- Total first-year cost: Rs 2,41,000
- First-year gross profit: Rs 1,79,000

### C) Studio Enterprise Tier (Monthly Expense)
Income:
- Monthly fee: Rs 65,000

Expenditure:
- Domain allocation: Rs 100
- Production + staging hosting: Rs 10,000
- Managed DB + backup + restore drills: Rs 4,000
- Storage + CDN + heavy file transfer: Rs 2,500
- Monitoring + security tooling: Rs 2,000
- Transactional messaging baseline: Rs 1,500
- Tools/licenses allocation: Rs 1,400
- Included support labor + escalation reserve: Rs 21,000
- Account management/reporting overhead: Rs 4,000
- Contingency reserve: Rs 2,500

Total monthly expenditure: Rs 49,000
Monthly operating profit: Rs 16,000
Operating margin: 25%

First-year (including setup at Rs 3,00,000):
- Revenue: Rs 3,00,000 + (Rs 65,000 x 12) = Rs 10,80,000
- Estimated onboarding/implementation cost: Rs 1,40,000
- Yearly recurring cost: Rs 49,000 x 12 = Rs 5,88,000
- Total first-year cost: Rs 7,28,000
- First-year gross profit: Rs 3,52,000

## 6) Quick Income vs Expenditure Summary Table (Monthly)

| Tier | Income | Expenditure | Profit | Margin |
|---|---:|---:|---:|---:|
| Foundation | Rs 10,000 | Rs 6,300 | Rs 3,700 | 37% |
| Professional | Rs 25,000 | Rs 15,500 | Rs 9,500 | 38% |
| Enterprise | Rs 65,000 | Rs 49,000 | Rs 16,000 | 25% |

## 7) Domain, Hosting, and Similar Charges (India Ballpark)
Domain annual:
- .in: Rs 700 to Rs 1,200
- .com: Rs 900 to Rs 1,600

Hosting monthly:
- Entry cloud/VPS: Rs 1,000 to Rs 2,500
- Mid tier cloud: Rs 3,000 to Rs 8,000
- High tier with staging and stronger SLAs: Rs 10,000 to Rs 35,000

Database + backups monthly:
- Small: Rs 400 to Rs 1,500
- Mid: Rs 1,200 to Rs 4,000
- High: Rs 4,000 to Rs 12,000

Storage + bandwidth monthly:
- Small: Rs 300 to Rs 1,000
- Mid: Rs 800 to Rs 3,000
- High: Rs 2,500 to Rs 10,000

Monitoring/security/tools monthly:
- Small: Rs 300 to Rs 1,000
- Mid: Rs 1,000 to Rs 3,000
- High: Rs 3,000 to Rs 12,000

## 8) Pricing Guardrail (Very Important)
Your enterprise tier at Rs 65,000 gives only about 25% margin in this model.
If you target 35% margin:
- Required revenue = Expenditure / (1 - 0.35)
- Required revenue = Rs 49,000 / 0.65 = about Rs 75,400

Practical recommendation:
- Keep Enterprise base at Rs 75,000 to Rs 85,000/month
- Or keep Rs 65,000 but reduce included support/custom hours

## 9) What to Bill Separately (Do Not Eat These Costs)
Bill as pass-through or add-on:
1. Payment gateway MDR and settlement charges
2. Bulk WhatsApp/SMS notifications
3. Major storage overage
4. One-off custom feature development
5. Data migration from legacy systems
6. Onsite training visits outside agreed scope
7. Third-party API subscription charges

## 10) Suggested Invoice Structure
Monthly invoice sections:
1. Base plan fee
2. Add-on services (if any)
3. Usage overages (storage/messages)
4. Taxes (GST)
5. Net payable

Setup invoice sections:
1. Implementation and onboarding
2. Data setup/migration
3. Initial training
4. Go-live checklist and acceptance

## 11) Practical Contract Notes
1. Keep minimum 6-month lock-in for first production clients.
2. Take setup fee 60% advance, 40% at go-live.
3. Monthly plan billed in advance.
4. Overage billed in arrears.
5. Define SLA response times per tier in writing.
6. Mention excluded items clearly to avoid hidden workload.

## 12) Final Recommendation for Your Next Step
Start with these launch prices for Gujarat:
- Foundation: Rs 10,000/month + Rs 50,000 setup
- Professional: Rs 25,000/month + Rs 1,20,000 setup
- Enterprise: start at least Rs 75,000/month + Rs 3,00,000 setup

If you keep Enterprise at Rs 65,000, reduce included support/customization commitments to protect margin.
