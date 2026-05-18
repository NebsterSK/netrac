<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ClipboardList,
    Dumbbell,
    LayoutGrid,
    Tags,
    TrendingUp,
    Wallet,
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import monthlyBalance from '@/routes/finance/monthly-balance';
import netWorth from '@/routes/finance/net-worth';
import categories from '@/routes/health/categories';
import exercises from '@/routes/health/exercises';
import sessions from '@/routes/health/sessions';
import type { NavGroup, NavItem } from '@/types';

const mainNavGroups: NavGroup[] = [
    {
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ],
    },
    {
        label: 'Finance',
        items: [
            {
                title: 'Monthly Balance',
                href: monthlyBalance.index(),
                icon: Wallet,
            },
            {
                title: 'Net Worth',
                href: netWorth.index(),
                icon: TrendingUp,
            },
        ],
    },
    {
        label: 'Health',
        items: [
            {
                title: 'Categories',
                href: categories.index(),
                icon: Tags,
            },
            {
                title: 'Exercises',
                href: exercises.index(),
                icon: Dumbbell,
            },
            {
                title: 'Sessions',
                href: sessions.index(),
                icon: ClipboardList,
            },
        ],
    },
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: FolderGit2,
    // }
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="mainNavGroups" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter
                v-if="footerNavItems.length > 0"
                :items="footerNavItems"
            />

            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
