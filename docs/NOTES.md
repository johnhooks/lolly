# NOTES.md

## Ideas

- HTTP Collectors for WP HTTP Client and WP REST Requests capture the request, but a Processor transforms them into PSR7 format which another Processor transforms into the Esc Field format. This would allow sharing logic between Laravel and WordPress logging setups.

  This seems like a good idea, thought the PSR-7 interface has a lot of requirements that are unnecessary for the purposes of logging.

### Capturing errors

Defining a custom `fatal-error-handler.php` dropin that extends [WP_Fatal_Error_Handler](https://developer.wordpress.org/reference/classes/wp_fatal_error_handler/), will allow better control to capture unhandled exceptions.
	- Add on install (maybe replace if missing).
	- Remove on uninstall.

## Refrences

- [PSR-7](https://www.php-fig.org/psr/psr-7/)
- [ECS Field Reference](https://www.elastic.co/guide/en/ecs/current/ecs-field-reference.html)
