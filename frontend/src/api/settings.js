// Centralized API endpoints and helper functions for settings
import { get, post } from './index'

export const endpoints = {
  calendar: '/settings/calendar',
  googleAuth: '/auth/google',
  calendarEnabled: '/calendar/google/enabled',
  revokeGoogleCalendar: '/calendar/google/revoke'
};

export default endpoints;

export const updateCalendarSetting = async (enabled) => {
  const response = await post(endpoints.calendar, { enabled: Boolean(enabled) })
  return response
}

export const getGoogleAuthUrl = async () => {
  const response = await get(endpoints.googleAuth)
  return response
}

export const pollCalendarEnabledStatus = async () => {
  const response = await get(endpoints.calendarEnabled)
  return response;
}

export const revokeGoogleCalendarIntegration = async () => {
  const response = await post(endpoints.revokeGoogleCalendar)
  return response;
}
