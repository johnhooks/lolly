import type { WpRestApiError } from './types';

/**
 * Type guard to check if an object is a WordPress REST API error response.
 *
 * @param error - The object to check
 * @return True if the object matches the WPRestApiError structure
 *
 * @example
 * try {
 *   await apiFetch({ path: '/wp/v2/posts' });
 * } catch (error) {
 *   if (isWPRestApiError(error)) {
 *     console.log('Error code:', error.code);
 *     console.log('Error message:', error.message);
 *   }
 * }
 */
export function isWpRestApiError(error: unknown): error is WpRestApiError {
    if (!isObject(error)) {
        return false;
    }

    if (typeof error.code !== 'string' || typeof error.message !== 'string') {
        return false;
    }

    if ('data' in error && isObject(error.data)) {
        const data = error.data;

        if ('status' in data && typeof data.status !== 'number') {
            return false;
        }

        if ('params' in data && isObject(data.params)) {
            const params = data.params;
            for (const value of Object.values(params)) {
                if (typeof value !== 'string') {
                    return false;
                }
            }
        }
    }

    if ('additional_errors' in error && error.additional_errors !== undefined) {
        if (!Array.isArray(error.additional_errors)) {
            return false;
        }

        // @todo Does each additional error have `data`?
        for (const additionalError of error.additional_errors) {
            if (
                !isObject(additionalError) ||
                typeof additionalError.code !== 'string' ||
                typeof additionalError.message !== 'string'
            ) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Extracts user-friendly error messages from various error types.
 * Returns an array of all error messages including additional errors.
 *
 * @param error - The error object
 *
 * @return An array of user-friendly error messages
 *
 * @example
 * catch (error) {
 *   const messages = getErrorMessages(error);
 *   setErrors(messages);
 * }
 */
export function getErrorMessages(error: unknown): string[] {
    if (isWpRestApiError(error)) {
        const messages: string[] = [];

        // Add main error message
        messages.push(error.message);

        // Add parameter-specific errors
        if (error.data?.params) {
            const paramErrors = Object.entries(error.data.params).map(
                ([key, value]) => `${key}: ${value}`
            );
            messages.push(...paramErrors);
        }

        // Add additional errors
        if (error.additional_errors) {
            const additionalMessages = error.additional_errors.map(
                (err) => err.message
            );
            messages.push(...additionalMessages);
        }

        return messages;
    }

    if (error instanceof Error) {
        return [error.message];
    }

    if (typeof error === 'string') {
        return [error];
    }

    return ['An unknown error occurred'];
}

/**
 * Extracts a single user-friendly error message from various error types.
 * Joins multiple errors with commas.
 *
 * @param error - The error object
 * @return A user-friendly error message
 *
 * @example
 * catch (error) {
 *   const message = getErrorMessage(error);
 *   setError(message);
 * }
 */
export function getErrorMessage(error: unknown): string {
    return getErrorMessages(error).join(', ');
}

/**
 * Object type guard.
 *
 * @param value
 *
 * @return Whether the value is an object.
 */
export function isObject(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
}

/**
 * Higher-order function which forwards the resolution to another resolver with the same arguments.
 *
 * @param {string} resolverName forwarded resolver.
 *
 * @return {Function} Enhanced resolver.
 */
export function forwardResolver(resolverName: string): any {
    return (...args: any) =>
        async ({ resolveSelect }: { resolveSelect: any }) => {
            await resolveSelect[resolverName](...args);
        };
}
