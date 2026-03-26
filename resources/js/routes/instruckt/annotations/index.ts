import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::index
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:16
 * @route '/instruckt/annotations'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/instruckt/annotations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::index
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:16
 * @route '/instruckt/annotations'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::index
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:16
 * @route '/instruckt/annotations'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::index
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:16
 * @route '/instruckt/annotations'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::index
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:16
 * @route '/instruckt/annotations'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::index
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:16
 * @route '/instruckt/annotations'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::index
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:16
 * @route '/instruckt/annotations'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
/**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::store
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:21
 * @route '/instruckt/annotations'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/instruckt/annotations',
} satisfies RouteDefinition<["post"]>

/**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::store
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:21
 * @route '/instruckt/annotations'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::store
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:21
 * @route '/instruckt/annotations'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::store
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:21
 * @route '/instruckt/annotations'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::store
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:21
 * @route '/instruckt/annotations'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::update
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:45
 * @route '/instruckt/annotations/{id}'
 */
export const update = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/instruckt/annotations/{id}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::update
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:45
 * @route '/instruckt/annotations/{id}'
 */
update.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return update.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::update
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:45
 * @route '/instruckt/annotations/{id}'
 */
update.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

    /**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::update
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:45
 * @route '/instruckt/annotations/{id}'
 */
    const updateForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PATCH',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \Instruckt\Laravel\Http\Controllers\AnnotationController::update
 * @see vendor/joshcirre/instruckt-laravel/src/Http/Controllers/AnnotationController.php:45
 * @route '/instruckt/annotations/{id}'
 */
        updateForm.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    update.form = updateForm
const annotations = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
}

export default annotations