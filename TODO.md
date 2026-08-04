# TODO

## Security ✓
- [x] Brute force protection on login (rate limiting)
- [x] CSRF protection on all forms
- [x] Session timeout / idle logout
- [x] Account email verification
- [x] Password strength policy enforcement

## Scalability ✓
- [x] Pagination on all list pages
- [x] Database query caching
- [x] Proper error logging / monitoring

## Reliability ✓
- [x] Automated database backup
- [x] Input validation (beyond basic escaping)

## Missing Features
- [ ] User registration flow (self-signup)
- [ ] Role-based access control (granular permissions)
- [ ] Google OAuth setup (`config/google_oauth.php`)
- [ ] SMTP email setup (`config/email.php`)
- [ ] Run migration: `ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER password_hash; ALTER TABLE users MODIFY password_hash VARCHAR(255) NULL;`
