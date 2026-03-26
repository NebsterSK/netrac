import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MonthlyBalanceController::index
 * @see app/Http/Controllers/MonthlyBalanceController.php:14
 * @route '/monthly-balance'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/monthly-balance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonthlyBalanceController::index
 * @see app/Http/Controllers/MonthlyBalanceController.php:14
 * @route '/monthly-balance'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonthlyBalanceController::index
 * @see app/Http/Controllers/MonthlyBalanceController.php:14
 * @route '/monthly-balance'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MonthlyBalanceController::index
 * @see app/Http/Controllers/MonthlyBalanceController.php:14
 * @route '/monthly-balance'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\MonthlyBalanceController::index
 * @see app/Http/Controllers/MonthlyBalanceController.php:14
 * @route '/monthly-balance'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\MonthlyBalanceController::index
 * @see app/Http/Controllers/MonthlyBalanceController.php:14
 * @route '/monthly-balance'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\MonthlyBalanceController::index
 * @see app/Http/Controllers/MonthlyBalanceController.php:14
 * @route '/monthly-balance'
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
* @see \App\Http\Controllers\MonthlyBalanceController::store
 * @see app/Http/Controllers/MonthlyBalanceController.php:24
 * @route '/monthly-balance'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/monthly-balance',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MonthlyBalanceController::store
 * @see app/Http/Controllers/MonthlyBalanceController.php:24
 * @route '/monthly-balance'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonthlyBalanceController::store
 * @see app/Http/Controllers/MonthlyBalanceController.php:24
 * @route '/monthly-balance'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\MonthlyBalanceController::store
 * @see app/Http/Controllers/MonthlyBalanceController.php:24
 * @route '/monthly-balance'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MonthlyBalanceController::store
 * @see app/Http/Controllers/MonthlyBalanceController.php:24
 * @route '/monthly-balance'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\MonthlyBalanceController::update
 * @see app/Http/Controllers/MonthlyBalanceController.php:31
 * @route '/monthly-balance/{monthly_balance}'
 */
export const update = (args: { monthly_balance: string | number } | [monthly_balance: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/monthly-balance/{monthly_balance}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\MonthlyBalanceController::update
 * @see app/Http/Controllers/MonthlyBalanceController.php:31
 * @route '/monthly-balance/{monthly_balance}'
 */
update.url = (args: { monthly_balance: string | number } | [monthly_balance: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monthly_balance: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    monthly_balance: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        monthly_balance: args.monthly_balance,
                }

    return update.definition.url
            .replace('{monthly_balance}', parsedArgs.monthly_balance.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonthlyBalanceController::update
 * @see app/Http/Controllers/MonthlyBalanceController.php:31
 * @route '/monthly-balance/{monthly_balance}'
 */
update.put = (args: { monthly_balance: string | number } | [monthly_balance: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})
/**
* @see \App\Http\Controllers\MonthlyBalanceController::update
 * @see app/Http/Controllers/MonthlyBalanceController.php:31
 * @route '/monthly-balance/{monthly_balance}'
 */
update.patch = (args: { monthly_balance: string | number } | [monthly_balance: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\MonthlyBalanceController::update
 * @see app/Http/Controllers/MonthlyBalanceController.php:31
 * @route '/monthly-balance/{monthly_balance}'
 */
    const updateForm = (args: { monthly_balance: string | number } | [monthly_balance: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MonthlyBalanceController::update
 * @see app/Http/Controllers/MonthlyBalanceController.php:31
 * @route '/monthly-balance/{monthly_balance}'
 */
        updateForm.put = (args: { monthly_balance: string | number } | [monthly_balance: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
            /**
* @see \App\Http\Controllers\MonthlyBalanceController::update
 * @see app/Http/Controllers/MonthlyBalanceController.php:31
 * @route '/monthly-balance/{monthly_balance}'
 */
        updateForm.patch = (args: { monthly_balance: string | number } | [monthly_balance: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    update.form = updateForm
/**
* @see \App\Http\Controllers\MonthlyBalanceController::destroy
 * @see app/Http/Controllers/MonthlyBalanceController.php:38
 * @route '/monthly-balance/{monthly_balance}'
 */
export const destroy = (args: { monthly_balance: string | number } | [monthly_balance: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/monthly-balance/{monthly_balance}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\MonthlyBalanceController::destroy
 * @see app/Http/Controllers/MonthlyBalanceController.php:38
 * @route '/monthly-balance/{monthly_balance}'
 */
destroy.url = (args: { monthly_balance: string | number } | [monthly_balance: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monthly_balance: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    monthly_balance: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        monthly_balance: args.monthly_balance,
                }

    return destroy.definition.url
            .replace('{monthly_balance}', parsedArgs.monthly_balance.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonthlyBalanceController::destroy
 * @see app/Http/Controllers/MonthlyBalanceController.php:38
 * @route '/monthly-balance/{monthly_balance}'
 */
destroy.delete = (args: { monthly_balance: string | number } | [monthly_balance: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\MonthlyBalanceController::destroy
 * @see app/Http/Controllers/MonthlyBalanceController.php:38
 * @route '/monthly-balance/{monthly_balance}'
 */
    const destroyForm = (args: { monthly_balance: string | number } | [monthly_balance: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroy.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\MonthlyBalanceController::destroy
 * @see app/Http/Controllers/MonthlyBalanceController.php:38
 * @route '/monthly-balance/{monthly_balance}'
 */
        destroyForm.delete = (args: { monthly_balance: string | number } | [monthly_balance: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
const MonthlyBalanceController = { index, store, update, destroy }

export default MonthlyBalanceController