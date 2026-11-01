import { usePage } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed } from 'vue';

type Owner = App.Data.Finance.OwnerData;

export type UseOwnersReturn = {
    owners: ComputedRef<Owner[]>;
    ownerFilter: ComputedRef<number | null>;
    defaultOwnerId: ComputedRef<number | null>;
    ownerById: (id: number | null | undefined) => Owner | null;
};

export function useOwners(): UseOwnersReturn {
    const page = usePage();

    const owners = computed(() => page.props.owners ?? []);
    const ownerFilter = computed(() => page.props.ownerFilter ?? null);
    const defaultOwnerId = computed(
        () => ownerFilter.value ?? page.props.auth?.user?.id ?? null,
    );

    function ownerById(id: number | null | undefined): Owner | null {
        if (id === null || id === undefined) {
            return null;
        }

        return owners.value.find((owner) => owner.id === id) ?? null;
    }

    return { owners, ownerFilter, defaultOwnerId, ownerById };
}
