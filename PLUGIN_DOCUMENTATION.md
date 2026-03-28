# ADHUB WordPress Plugin - Technical Documentation

## Overview

ADHUB is a WordPress plugin that connects your website forms to the ADHUB CRM system. It automatically captures form submissions and sends them to ADHUB as leads.

---

## Plugin Information

| Property | Value |
|----------|-------|
| **Plugin Name** | ADHUB |
| **Version** | 1.0.1 |
| **WordPress Version** | 5.2+ |
| **PHP Version** | 7.2+ |
| **License** | GPL v2 or later |

---

## What The Plugin Does

1. **Detects Forms** - Automatically detects installed form plugins on your WordPress site
2. **Connects to ADHUB** - Authenticates using API key from ADHUB dashboard
3. **Captures Submissions** - Intercepts form submissions in real-time
4. **Maps Fields** - Converts form fields to ADHUB lead fields
5. **Sends to API** - Creates leads in ADHUB CRM automatically

---

## Supported Form Plugins

| # | Form Plugin | WordPress Hook Used |
|---|-------------|---------------------|
| 1 | Contact Form 7 | `wpcf7_before_send_mail` |
| 2 | WPForms | `wpforms_process_complete` |
| 3 | Fluent Forms | `fluentform/after_submission_inserted` |
| 4 | Formidable Forms | `frm_after_create_entry` |
| 5 | Forminator | `forminator_custom_form_submit_after` |
| 6 | Elementor Pro Forms | `elementor_pro/forms/new_record` |

---

## API Authentication

### How It Works

```
User enters API key in plugin settings
         ↓
API key saved to WordPress as 'adhub_token'
         ↓
Every API request includes: Authorization: Bearer adhub_live_xxxxx
         ↓
ADHUB API validates the token
```

### API Endpoints Used

| Endpoint | Purpose |
|----------|---------|
| `POST /api/v1/leads` | Create new lead |
| `GET /api/v1/lead-statuses` | Get status UUIDs |
| `GET /api/v1/lead-sources` | Get source UUIDs |
| `GET /api/v1/query-builder/fields` | Get filter fields |

### Base URL
```
https://adhub-main-d1fcap.laravel.cloud/api/v1
```

---

## Field Mapping

### First Name Detection (Priority Order)
```
name → names → your-name → your_name → full-name → full_name → 
fullname → first-name → first_name → contact-name → customer-name → user-name
```

If full name given (e.g., "John Doe"):
- `first_name` = "John"
- `last_name` = "Doe"

### Email Detection
```
email → your-email → your_email → user_email → 
email-address → email_address → contact-email → Email
```

### Phone Detection
```
phone → your-phone → your_phone → tel → telephone → mobile → 
contact-phone → phone-number → phone_number → Phone
```

**Phone Formatting:**
- 10 digits: `(XXX) XXX-XXXX`
- 11 digits starting with 1: `+1 (XXX) XXX-XXXX`

### Other Fields
All remaining form fields are combined into `internal_notes` as key-value pairs.

---

## Required API Fields

| Field | Required | How Obtained |
|-------|----------|--------------|
| `first_name` | Yes | From form field mapping |
| `email` | Yes | From form field mapping |
| `status_id` | Yes | Fetched from `/lead-statuses` API |
| `source_id` | Yes | Fetched from `/lead-sources` API |
| `mobile_number` | No | From form field mapping |
| `company` | No | From form field mapping |
| `job_title` | No | From form field mapping |
| `internal_notes` | No | Combined from extra fields |

---

## Architecture

### File Structure
```
adhub.php                    # Main plugin file
├── includes/
│   ├── class-api-sender.php           # API communication
│   ├── class-adhub-form-hooks.php     # Form hook registration
│   ├── class-adhub-forms-scanner.php  # Form detection
│   └── form-handlers/
│       ├── class-base-handler.php              # Base class
│       ├── class-contact-form-7-handler.php   # CF7
│       ├── class-fluent-forms-handler.php     # Fluent
│       └── ... (other handlers)
└── admin/
    └── class-adhub-admin.php         # Settings page
```

### Data Flow
```
Form Submit → Form Handler → Base Handler → API Sender → ADHUB API
                    ↓
            Check if form enabled
                    ↓
            Map fields to ADHUB format
                    ↓
            Get status_id/source_id UUIDs
                    ↓
            POST to /api/v1/leads
```

---

## WordPress Options Used

| Option | Description |
|--------|-------------|
| `adhub_token` | API key for authentication |
| `adhub_verification_status` | Connection status (pending/verified) |
| `adhub_connected_forms` | Array of detected form plugins |
| `adhub_enabled_forms` | Array of enabled form IDs |

---

## Error Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 401 | Unauthorized (invalid API key) |
| 422 | Validation error (missing/invalid fields) |
| 500 | Server error |

---

## Debugging

### Enable Debug Mode
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### View Debug Info
Use shortcode: `[adhub_debug]`



---

## Changelog

### 1.0.1
- Initial release
- 

- Fixed: Form handler hooks registration
- Added: Debug logging