<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Circle,
    EllipsisVertical,
    Eye,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import sessionRoutes from '@/routes/health/sessions';
import type { BreadcrumbItem } from '@/types';

type SessionRow = App.Data.Health.SessionListItemData;

defineProps<{
    sessions: SessionRow[];
}>();

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function deleteSession(session: SessionRow) {
    if (!confirm('Delete this session?')) {
        return;
    }

    router.delete(sessionRoutes.destroy.url(session.id));
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Sessions',
        href: sessionRoutes.index(),
    },
];
</script>

<template>
    <Head title="Sessions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-4 p-4 lg:max-w-3xl">
            <Card class="self-start">
                <CardHeader>
                    <CardTitle class="leading-8">Sessions</CardTitle>

                    <CardAction>
                        <Button
                            as-child
                            size="icon-sm"
                            variant="outline"
                            class="cursor-pointer"
                        >
                            <Link :href="sessionRoutes.create()">
                                <Plus class="size-3" />
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent>
                    <p
                        v-if="sessions.length === 0"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        No sessions yet. Create one to get started.
                    </p>

                    <table
                        v-else
                        class="w-full text-left text-sm"
                        aria-label="Workout sessions"
                    >
                        <tbody>
                            <tr
                                v-for="session in sessions"
                                :key="session.id"
                                class="group/row border-b transition-colors last:border-0 hover:bg-muted/50"
                            >
                                <td class="w-0 pl-4">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <button
                                                class="flex h-full cursor-pointer items-center"
                                                aria-label="Actions"
                                            >
                                                <EllipsisVertical
                                                    class="size-4 text-muted-foreground"
                                                />
                                            </button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="start">
                                            <DropdownMenuItem as-child>
                                                <Link
                                                    :href="
                                                        sessionRoutes.show(
                                                            session.id,
                                                        )
                                                    "
                                                    class="flex w-full cursor-pointer items-center gap-2"
                                                >
                                                    <Eye class="size-4" />
                                                    Open
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                class="cursor-pointer text-red-600 dark:text-red-400"
                                                @click="deleteSession(session)"
                                            >
                                                <Trash2 class="size-4" />
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>

                                <td class="px-4 py-3">
                                    <Link
                                        :href="sessionRoutes.show(session.id)"
                                        class="hover:underline"
                                    >
                                        {{ formatDate(session.performed_at) }}
                                    </Link>
                                </td>

                                <td
                                    class="px-4 py-3 text-right text-muted-foreground"
                                >
                                    <span
                                        class="inline-flex items-center gap-1.5"
                                    >
                                        <CheckCircle2
                                            v-if="
                                                session.completed ===
                                                    session.total &&
                                                session.total > 0
                                            "
                                            class="size-4 text-green-600 dark:text-green-400"
                                        />

                                        <Circle v-else class="size-4" />

                                        {{ session.completed }} /
                                        {{ session.total }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
