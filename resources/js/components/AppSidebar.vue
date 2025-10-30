<script setup lang="ts">
import { Home as HomeIcon, BellRing as PingIcon, TextCursorInput as PromptsIcon, Library as ResourcesIcon, Hammer as ToolsIcon } from 'lucide-vue-next'
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar'
import { Link } from '@inertiajs/vue3'
import AppLogo from './AppLogo.vue'

const items = [
    {
        title: 'Home',
        href: '/',
        icon: HomeIcon,
    },
    {
        title: 'Tools',
        href: '/tools',
        icon: ToolsIcon,
    },
    {
        title: 'Prompts',
        href: '#',
        icon: PromptsIcon,
    },
    {
        title: 'Resources',
        href: '#',
        icon: ResourcesIcon,
    },
    {
        title: 'Ping',
        href: '/ping',
        icon: PingIcon,
    },
]
</script>

<template>
    <Sidebar collapsible="icon">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/">
                            <AppLogo/>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupLabel>Application</SidebarGroupLabel>

                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in items"
                            :key="item.title"
                        >
                            <SidebarMenuButton
                                :tooltip="item.title"
                                size="lg"
                                asChild
                            >
                                <Link :href="item.href">
                                    <component :is="item.icon"/>

                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>
    </Sidebar>
</template>
