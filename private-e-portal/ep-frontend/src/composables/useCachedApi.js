/**
 * Stale-While-Revalidate (SWR) In-Memory Request Caching Composable
 *
 * Implements SWR caching for low-frequency, high-read endpoints like:
 * - Employee 201 File & Personal Records
 * - SALN Statements & Properties
 * - Employee Competencies & Skill Ratings
 * - Training & Seminar Records
 */

const cache = new Map()

export function useCachedApi() {
  /**
   * Retrieves an item from cache.
   * @param {string} cacheKey
   * @param {number} ttlMs Time to live in ms (default: 5 minutes)
   * @returns {{ data: any, isStale: boolean } | null}
   */
  function getCached(cacheKey, ttlMs = 300000) {
    const entry = cache.get(cacheKey)
    if (!entry) return null
    const isStale = (Date.now() - entry.timestamp) > ttlMs
    return { data: entry.data, isStale }
  }

  /**
   * Stores an item into memory cache.
   * @param {string} cacheKey
   * @param {any} data
   */
  function setCache(cacheKey, data) {
    cache.set(cacheKey, { data, timestamp: Date.now() })
  }

  /**
   * Invalidates a single key or any key starting with a given prefix.
   * @param {string} cacheKeyOrPrefix
   */
  function mutate(cacheKeyOrPrefix) {
    if (cache.has(cacheKeyOrPrefix)) {
      cache.delete(cacheKeyOrPrefix)
      return
    }
    for (const key of cache.keys()) {
      if (key.startsWith(cacheKeyOrPrefix)) {
        cache.delete(key)
      }
    }
  }

  /**
   * Wipes the entire in-memory cache (invoked on logout or session reset).
   */
  function clearCache() {
    cache.clear()
  }

  /**
   * Executes Stale-While-Revalidate workflow:
   * 1. If cache hit: immediately passes cached data to onData (0ms UI render, no skeleton).
   *    If stale: fires fetcher in background and calls onData on completion to silently sync.
   * 2. If cold start: sets loading true, awaits fetcher, stores to cache, and sets loading false.
   */
  async function swrExecute({
    key,
    fetcher,
    ttlMs = 300000,
    onData = () => {},
    onLoading = () => {},
    onError = () => {}
  }) {
    const cached = getCached(key, ttlMs)

    if (cached) {
      // Immediate 0ms cache delivery
      onData(cached.data, false)
      onLoading(false)

      // Silent background revalidation if stale
      if (cached.isStale) {
        fetcher()
          .then((fresh) => {
            if (fresh) {
              setCache(key, fresh)
              onData(fresh, true)
            }
          })
          .catch((err) => {
            console.warn(`[SWR] Background revalidation failed for ${key}:`, err)
          })
      }
      return cached.data
    }

    // Cold start (no cache in memory)
    try {
      onLoading(true)
      const fresh = await fetcher()
      if (fresh) {
        setCache(key, fresh)
        onData(fresh, false)
      }
      return fresh
    } catch (err) {
      onError(err)
      throw err
    } finally {
      onLoading(false)
    }
  }

  return {
    getCached,
    setCache,
    mutate,
    clearCache,
    swrExecute
  }
}

export const swrCache = useCachedApi()
