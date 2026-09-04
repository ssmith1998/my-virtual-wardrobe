<template>
<h6>Settings</h6>

 <div v-if="initialLoading" class="settings-loading">
    <q-spinner-dots size="40" color="primary" />
    <span style="margin-left:12px">Loading settings…</span>
  </div>

 <div v-else>
    <q-toggle
      v-model="calendarEnabled"
      :disable="loading"
      color="pink"
      icon="calendar_today"
      label="Enable Google Calendar Integration"
      @update:model-value="onEnableCalendarIntegration"
    />
  </div>

</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { pollCalendarEnabledStatus } from '../api/settings'
import { usePoll } from '../composables/usePoll'

const calendarEnabled = ref(false);
const loading = ref(false);
const initialLoading = ref(true)

const { data: pollData, error: pollError, start: startPoll, stop: stopPoll } = usePoll(pollCalendarEnabledStatus, 5000)

watch(pollData, (val) => {
  if (val && typeof val.enabled !== 'undefined') {
    calendarEnabled.value = val.enabled
    initialLoading.value = false
  }
})

watch(pollError, (err) => {
  if (err) {
    // stop initial loading even on error so UI becomes interactive
    initialLoading.value = false
  }
})

onMounted(() => startPoll(true))
onUnmounted(() => stopPoll())

const onEnableCalendarIntegration = async (value) => {
  // value is the new boolean from the toggle
  calendarEnabled.value = value
  loading.value = true
  try {
    // If enabling integration, request the Google OAuth URL and navigate there
    if (value) {
      try {
        window.location.href = 'https://localhost:8000/api/auth/google'
      } catch (e) {
        console.error('Failed to redirect to Google auth', e)
      }
    }
  } catch (err) {
    console.error('Failed to update calendar setting', err)
    // revert toggle state on error
    calendarEnabled.value = !value
  } finally {
    loading.value = false
  }
}
</script>