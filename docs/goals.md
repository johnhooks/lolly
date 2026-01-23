# Lolly Goals

## Purpose

Lolly is a logging bridge for WordPress. It captures events from WordPress and writes them to a log file in ECS (Elastic Common Schema) format for ingestion into an ELK stack.

## Target Users

### Enterprise Teams

Teams with existing logging infrastructure who want WordPress integrated into their centralized logging and observability stack.

### WordPress Developers

Developers who need visibility into WordPress internals during development or debugging. The HTTP client logging is particularly valuable - any plugin that uses the WP HTTP API (`wp_remote_get`, `wp_remote_post`, etc.) will have its outbound requests logged automatically. This provides insight into what third-party plugins are doing, when external API calls happen, and how long they take - without needing to dig through plugin code or add custom debugging.

## Core Goals

### 1. ELK-Native Formatting

Logs are formatted in strict ECS format so they integrate seamlessly with other ECS-formatted sources (nginx, application servers, etc.) in the same Elasticsearch cluster.

The formatter is swappable - ECS is the default, but alternative formatters could be implemented.

### 2. Minimal Performance Impact

Light touch on WordPress - logging should not noticeably impact site performance.

Strategies:
- File writes over database writes
- Buffer log entries and write in batches where possible
- Write after the response is sent (`shutdown` hook)
- Disabled features register no hooks (zero overhead)
- Cache settings on init - don't hit the database on every request

### 3. Comprehensive Event Capture

Ability to log everything relevant - HTTP requests, REST API calls, user events, plugin changes, etc.

Each event type is independently configurable. Log liberally, filter in Kibana.

### 4. Sensitive Data Redaction

Make it safe to log liberally. Redaction rules prevent passwords, tokens, and other sensitive data from leaking into logs.

### 5. Leverage the ELK Ecosystem

Don't reinvent what ELK does well. Lolly captures and formats - Elasticsearch stores, Kibana visualizes, and your SIEM alerts.

## Non-Goals

Lolly explicitly does not:

- **Provide a log viewer** - Use Kibana
- **Build an alerting system** - Use ELK alerting or your SIEM. However, Monolog's handler architecture allows optional notifications for critical events (ALERT level and above)
- **Handle log rotation** - Use logrotate or your log shipper
- **Store logs in the database** - File-based only
- **Target casual users** - This is for teams with existing logging infrastructure

## Architecture

### File-Based Logging

Lolly writes logs to a file, which is then shipped to ELK via Filebeat or similar. This decouples WordPress from ELK availability - if Elasticsearch goes down, the site keeps working and logs queue up in the file.

### Built on Monolog

Lolly uses Monolog as its logging library. While file-based logging is the current approach, Monolog's handler architecture means other destinations are possible in the future (direct to Elasticsearch, Logstash via TCP/GELF, Redis, etc.) without changing how events are captured.

## Future Considerations

### Remote Log Shipping

Monolog supports handlers that ship logs directly to remote endpoints over HTTPS, UDP, or TCP/TLS. This could enable configurations where logs are sent to a remote ELK instance without requiring a local agent like Filebeat - the customer would simply configure an endpoint URL and credentials.

## Design Principles

### Configurable, Not Opinionated

Every feature is togglable. Disabled features have zero runtime cost.

### Schema-Validated Configuration

Settings are stored as a WordPress option and validated against a strict JSON schema. The schema ensures configuration integrity and enables the REST API to expose settings safely. Settings are autoloaded by WordPress and cached in memory for the duration of the request.

### Processors Enrich Context

Monolog processors transform WordPress objects (WP_User, WP_Error, HTTP responses) into ECS-compliant structures. This keeps listeners simple - they capture the event, processors handle the formatting.

### Listeners Are Focused

Each listener handles one event type. They hook into WordPress actions, capture relevant data, and log. Business logic stays minimal.
