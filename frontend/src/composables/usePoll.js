import { ref, onUnmounted } from 'vue'

export function usePoll(fetchFn, intervalMs = 5000) {
  const data = ref(null)
  const error = ref(null)
  const running = ref(false)

  let timer = null
  let isFetching = false

  const tick = async () => {
    if (isFetching) return
    isFetching = true
    try {
      const result = await fetchFn()
      data.value = result
      error.value = null
    } catch (err) {
      error.value = err
    } finally {
      isFetching = false
    }
  }

  const start = (immediate = true) => {
    if (running.value) return
    running.value = true
    if (immediate) tick()
    timer = setInterval(tick, intervalMs)
  }

  const stop = () => {
    running.value = false
    if (timer) {
      clearInterval(timer)
      timer = null
    }
  }

  onUnmounted(stop)

  return { data, error, running, start, stop, tick }
}
