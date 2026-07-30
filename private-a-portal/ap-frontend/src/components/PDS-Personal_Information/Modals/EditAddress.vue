<template>
    <div class="p-6 bg-white border rounded-lg shadow">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ title }}</h3>
      
      <div class="space-y-4">
        <div v-if="type === 'permanent'" class="flex items-center">
          <input 
            id="same-address"
            type="checkbox" 
            v-model="sameAsResidential"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          >
          <label for="same-address" class="ml-2 block text-sm text-gray-700">
            Same as Residential Address
          </label>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Region <span class="text-red-500">*</span></label>
          <select 
            v-model="localInfo[`${prefix}_region`]"
            @change="handleRegionChange"
            :disabled="type === 'permanent' && sameAsResidential"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
          >
            <option value="">Select Region</option>
            <!-- Store region description (regDesc) instead of code -->
            <option
              v-for="region in regions"
              :key="region.regCode"
              :value="region.regDesc"
            >
              {{ region.regDesc }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Province <span class="text-red-500">*</span></label>
          <select 
            v-model="localInfo[`${prefix}_province`]"
            @change="handleProvinceChange"
            :disabled="!localInfo[`${prefix}_region`] || (type === 'permanent' && sameAsResidential)"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
          >
            <option value="">Select Province</option>
            <!-- Store province description (provDesc) instead of code -->
            <option
              v-for="province in availableProvinces"
              :key="province.provCode"
              :value="province.provDesc"
            >
              {{ province.provDesc }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">City/Municipality <span class="text-red-500">*</span></label>
          <select 
            v-model="localInfo[`${prefix}_city`]"
            @change="handleCityChange"
            :disabled="!localInfo[`${prefix}_province`] || (type === 'permanent' && sameAsResidential)"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
          >
            <option value="">Select City/Municipality</option>
            <!-- Store city description (citymunDesc) instead of code -->
            <option
              v-for="city in availableCities"
              :key="city.citymunCode"
              :value="city.citymunDesc"
            >
              {{ city.citymunDesc }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Barangay <span class="text-red-500">*</span></label>
          <select 
            v-model="localInfo[`${prefix}_barangay`]"
            :disabled="!localInfo[`${prefix}_city`] || (type === 'permanent' && sameAsResidential)"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
          >
            <option value="">Select Barangay</option>
            <!-- Store barangay description (brgyDesc) instead of code -->
            <option
              v-for="barangay in availableBarangays"
              :key="barangay.brgyCode"
              :value="barangay.brgyDesc"
            >
              {{ barangay.brgyDesc }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">House No./Block/Lot</label>
          <input 
            v-model="localInfo[`${prefix}_house_no`]"
            type="text" 
            :disabled="type === 'permanent' && sameAsResidential"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
            placeholder="Enter house number"
          >
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Street <span class="text-red-500">*</span></label>
          <input 
            v-model="localInfo[`${prefix}_street`]"
            type="text" 
            :disabled="type === 'permanent' && sameAsResidential"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
            placeholder="Enter street"
          >
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Village/Subdivision <span class="text-red-500">*</span></label>
          <input 
            v-model="localInfo[`${prefix}_village`]"
            type="text" 
            :disabled="type === 'permanent' && sameAsResidential"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
            placeholder="Enter village/subdivision"
          >
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, watch, computed, nextTick } from 'vue'
  
  import regionsData from '@/assets/refregion.json'
  import provincesData from '@/assets/refprovince.json'
  import citiesData from '@/assets/refcitymun.json'
  import barangaysData from '@/assets/refbrgy.json'
  
  const props = defineProps({
    info: { type: Object, required: true },
    type: { type: String, required: true },
    title: { type: String, required: true },
    prefix: { type: String, required: true },
    residentialData: { type: Object, default: () => ({}) }
  })
  
  const emit = defineEmits(['update'])
  
  const localInfo = ref({ ...props.info })
  const sameAsResidential = ref(false)
  
  const regions = ref(regionsData.RECORDS || [])
  const allProvinces = ref(provincesData.RECORDS || [])
  const allCities = ref(citiesData.RECORDS || [])
  const allBarangays = ref(barangaysData.RECORDS || [])
  
  const availableProvinces = computed(() => {
    const selectedRegion = localInfo.value[`${props.prefix}_region`]
    if (!selectedRegion) return []

    // When saving we store the region description; for filtering we need the region code
    const regionObj = regions.value.find(
      (region) =>
        region.regDesc === selectedRegion || region.regCode === selectedRegion
    )
    if (!regionObj) return []

    const regionCode = regionObj.regCode
    return allProvinces.value.filter((province) => province.regCode === regionCode)
  })
  
  const availableCities = computed(() => {
    const selectedProvince = localInfo.value[`${props.prefix}_province`]
    if (!selectedProvince) return []

    // When saving we store the province description; for filtering we need the province code
    const provinceObj = allProvinces.value.find(
      (province) =>
        province.provDesc === selectedProvince || province.provCode === selectedProvince
    )
    if (!provinceObj) return []

    const provinceCode = provinceObj.provCode
    return allCities.value.filter((city) => city.provCode === provinceCode)
  })
  
  const availableBarangays = computed(() => {
    const selectedCity = localInfo.value[`${props.prefix}_city`]
    if (!selectedCity) return []

    // When saving we store the city description; for filtering we need the city code
    const cityObj = allCities.value.find(
      (city) =>
        city.citymunDesc === selectedCity || city.citymunCode === selectedCity
    )
    if (!cityObj) return []

    const cityCode = cityObj.citymunCode
    return allBarangays.value.filter(
      (barangay) => barangay.citymunCode === cityCode
    )
  })

  const syncPermanentFromResidential = async (residential) => {
    localInfo.value.pa_region = residential?.ra_region || ''
    await nextTick()
    localInfo.value.pa_province = residential?.ra_province || ''
    await nextTick()
    localInfo.value.pa_city = residential?.ra_city || ''
    await nextTick()
    localInfo.value.pa_barangay = residential?.ra_barangay || ''
    localInfo.value.pa_house_no = residential?.ra_house_no || ''
    localInfo.value.pa_street = residential?.ra_street || ''
    localInfo.value.pa_village = residential?.ra_village || ''
  }
  
  watch(sameAsResidential, async (isChecked) => {
    if (isChecked && props.type === 'permanent') {
      await syncPermanentFromResidential(props.info)
      emit('update', { ...localInfo.value })
    } else if (!isChecked && props.type === 'permanent') {
      localInfo.value.pa_region = ''
      localInfo.value.pa_province = ''
      localInfo.value.pa_city = ''
      localInfo.value.pa_barangay = ''
      localInfo.value.pa_house_no = ''
      localInfo.value.pa_street = ''
      localInfo.value.pa_village = ''
      emit('update', { ...localInfo.value })
    }
  })
  
  const handleRegionChange = () => {
    localInfo.value[`${props.prefix}_province`] = ''
    localInfo.value[`${props.prefix}_city`] = ''
    localInfo.value[`${props.prefix}_barangay`] = ''
  }
  
  const handleProvinceChange = () => {
    localInfo.value[`${props.prefix}_city`] = ''
    localInfo.value[`${props.prefix}_barangay`] = ''
  }
  
  const handleCityChange = () => {
    localInfo.value[`${props.prefix}_barangay`] = ''
  }
  
  watch(localInfo, (newValue) => {
    emit('update', newValue)
  }, { deep: true })
  
  watch(() => {
    if (props.type === 'permanent' && sameAsResidential.value) {
      return {
        ra_region: props.info.ra_region,
        ra_province: props.info.ra_province,
        ra_city: props.info.ra_city,
        ra_barangay: props.info.ra_barangay,
        ra_house_no: props.info.ra_house_no,
        ra_street: props.info.ra_street,
        ra_village: props.info.ra_village,
      }
    }
    return null
  }, async (residentialData) => {
    if (residentialData && props.type === 'permanent' && sameAsResidential.value) {
      await syncPermanentFromResidential(residentialData)
      emit('update', { ...localInfo.value })
    }
  }, { deep: true })
  
  watch(() => props.info, (newValue) => {
    if (!(props.type === 'permanent' && sameAsResidential.value)) {
      Object.assign(localInfo.value, newValue)
    } else if (props.type === 'permanent' && sameAsResidential.value) {
      const { ra_region, ra_province, ra_city, ra_barangay, ra_house_no, ra_street, ra_village, ...rest } = newValue
      Object.assign(localInfo.value, rest)
    }
  }, { deep: true })
  </script>
  