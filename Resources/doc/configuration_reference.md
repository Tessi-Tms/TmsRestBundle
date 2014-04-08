TmsRestBundle Configuration Reference
=====================================

REST configuration
-------------------------

#### Configuration
You can override default configuration in `app/config/config.yml` file :
```yml
tms_rest:
    default:
        pagination_limit:
            default: X
            maximum: X
    routes:
        api_offers_get_offers:
            pagination_limit:
                default: X
                maximum: X
```

