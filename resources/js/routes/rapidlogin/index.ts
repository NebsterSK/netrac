import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
 * @see vendor/veltisan/rapidlogin/src/RapidLoginProvider.php:36
 * @route '/_rapidlogin/login/{user}'
 */
export const login = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(args, options),
    method: 'get',
})

login.definition = {
    methods: ["get","head"],
    url: '/_rapidlogin/login/{user}',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see vendor/veltisan/rapidlogin/src/RapidLoginProvider.php:36
 * @route '/_rapidlogin/login/{user}'
 */
login.url = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { user: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { user: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    user: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        user: typeof args.user === 'object'
                ? args.user.id
                : args.user,
                }

    return login.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
 * @see vendor/veltisan/rapidlogin/src/RapidLoginProvider.php:36
 * @route '/_rapidlogin/login/{user}'
 */
login.get = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(args, options),
    method: 'get',
})
/**
 * @see vendor/veltisan/rapidlogin/src/RapidLoginProvider.php:36
 * @route '/_rapidlogin/login/{user}'
 */
login.head = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: login.url(args, options),
    method: 'head',
})

    /**
 * @see vendor/veltisan/rapidlogin/src/RapidLoginProvider.php:36
 * @route '/_rapidlogin/login/{user}'
 */
    const loginForm = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: login.url(args, options),
        method: 'get',
    })

            /**
 * @see vendor/veltisan/rapidlogin/src/RapidLoginProvider.php:36
 * @route '/_rapidlogin/login/{user}'
 */
        loginForm.get = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: login.url(args, options),
            method: 'get',
        })
            /**
 * @see vendor/veltisan/rapidlogin/src/RapidLoginProvider.php:36
 * @route '/_rapidlogin/login/{user}'
 */
        loginForm.head = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: login.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    login.form = loginForm
const rapidlogin = {
    login: Object.assign(login, login),
}

export default rapidlogin