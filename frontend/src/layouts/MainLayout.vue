<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>
        <q-btn flat dense round icon="menu" aria-label="Menu" @click="toggleLeftDrawer" />

        <q-toolbar-title> My Virtual Wardrobe </q-toolbar-title>
      </q-toolbar>
    </q-header>

    <q-drawer v-model="leftDrawerOpen" show-if-above bordered>
      <q-list>
        <q-item-label header> My Virtual Wardrobe </q-item-label>

        <SidebarLinks v-for="link in linksList" :key="link.title" v-bind="link" />
      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref } from 'vue'
import SidebarLinks from '../components/SiderbarLinks.vue'

const linksList = [
  {
    title: 'Wardrobe',
    icon: 'photo_library',
    link: 'wardrobe',
  },
  {
    title: 'Outfit Explorer',
    icon: 'explore',
    link: 'outfit-explorer',
  },
]

const leftDrawerOpen = ref(false)

function toggleLeftDrawer() {
  leftDrawerOpen.value = !leftDrawerOpen.value
}
</script>
