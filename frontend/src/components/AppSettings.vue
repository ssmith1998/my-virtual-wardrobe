<template>
  <div class="settings-page">
    <q-card flat bordered class="settings-card">
      <q-card-section class="header-section">
        <div class="row items-center no-wrap">
          <div class="icon-wrap q-mr-md">
            <q-icon name="settings" size="28px" color="primary" />
          </div>
          <div>
            <div class="text-h5 text-weight-medium">Settings</div>
            <div class="text-subtitle2 text-grey-7">Manage your wardrobe preferences.</div>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div v-if="initialLoading" class="settings-loading">
          <q-spinner-dots size="40" color="primary" />
          <span class="loading-label">Loading settings…</span>
        </div>

        <div v-else class="settings-row">
          <div class="title-group">
            <div class="text-subtitle1 text-weight-medium">Google Calendar</div>
            <div class="text-body2 text-grey-7">
              Enable calendar sync to factor your schedule into outfit recommendations.
            </div>
          </div>

          <q-toggle
            v-model="calendarEnabled"
            :disable="loading"
            color="primary"
            icon="calendar_today"
            label="Enable integration"
            size="lg"
            @update:model-value="onEnableDisableCalendarIntegration"
          />
        </div>
      </q-card-section>
    </q-card>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { pollCalendarEnabledStatus, revokeGoogleCalendarIntegration } from '../api/settings'
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

const onEnableDisableCalendarIntegration = async (value) => {
  // value is the new boolean from the toggle
  calendarEnabled.value = value
  loading.value = true
  try {
    // If enabling integration, request the Google OAuth URL and navigate there
    if (value) {
        calendarEnabled.value = true
      try {
        window.location.href = 'https://localhost:8000/api/auth/google'
      } catch (e) {
        console.error('Failed to redirect to Google auth', e)
      }
    } else {
      // If disabling integration, call the API to revoke access
      await revokeGoogleCalendarIntegration();
      calendarEnabled.value = false
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

<style scoped>
.settings-page {
  display: flex;
  justify-content: center;
  padding: 24px 16px;
}

.settings-card {
  width: min(100%, 720px);
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.72);
  backdrop-filter: blur(10px);
}

.header-section {
  padding: 24px 24px 18px;
}

.icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
  border-radius: 14px;
  background: rgba(103, 80, 164, 0.12);
}

.settings-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 120px;
  color: #666;
}

.loading-label {
  margin-left: 12px;
  font-size: 0.95rem;
}

.settings-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding: 8px 4px;
}

.title-group {
  flex: 1;
}

@media (max-width: 640px) {
  .settings-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
