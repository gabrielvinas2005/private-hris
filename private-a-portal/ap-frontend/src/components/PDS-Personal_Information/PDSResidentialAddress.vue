<template>
  <div class="bg-white rounded-lg shadow-sm border">
    <div class="bg-white text-gray-800 px-6 py-3 border-b rounded-t-lg">
      <h2 class="text-sm font-semibold flex items-center">
        <i class="fas fa-home mr-1"></i>
        RESIDENTIAL ADDRESS
      </h2>
    </div>

    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
            Region
          </label>
          <div class="border-b border-gray-300 pb-1 min-h-[24px]">
            {{ getRegionName(info.ra_region) || "" }}
          </div>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
            Province
          </label>
          <div class="border-b border-gray-300 pb-1 min-h-[24px]">
            {{ getProvinceName(info.ra_province) || "" }}
          </div>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
            City/Municipality
          </label>
          <div class="border-b border-gray-300 pb-1 min-h-[24px]">
            {{ getCityName(info.ra_city) || "" }}
          </div>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
            Barangay
          </label>
          <div class="border-b border-gray-300 pb-1 min-h-[24px]">
            {{ getBarangayName(info.ra_barangay) || "" }}
          </div>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
            Subdivision/village
          </label>
          <div class="border-b border-gray-300 pb-1 min-h-[24px]">
            {{ info.ra_village || "" }}
          </div>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
            Street
          </label>
          <div class="border-b border-gray-300 pb-1 min-h-[24px]">
            {{ info.ra_street || "" }}
          </div>
        </div>
        <div class="md:col-span-3">
          <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
            House/block/lot no.
          </label>
          <div class="border-b border-gray-300 pb-1 min-h-[24px]">
            {{ info.ra_house_no || "" }}
          </div>
        </div>
        <div class="md:col-span-3"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import regionsData from "@/assets/refregion.json";
import provincesData from "@/assets/refprovince.json";
import citiesData from "@/assets/refcitymun.json";
import barangaysData from "@/assets/refbrgy.json";

const props = defineProps({
  info: { type: Object, required: true },
});

const getRegionName = (code) => {
  if (!code) return "";
  const codeStr = String(code).trim();
  let region = regionsData.RECORDS.find((r) => r.regCode === codeStr);
  if (!region) {
    region = regionsData.RECORDS.find(
      (r) => r.regCode === codeStr.padStart(2, "0")
    );
  }
  return region ? region.regDesc : code;
};

const getProvinceName = (code) => {
  if (!code) return "";
  const codeStr = String(code).trim();
  let province = provincesData.RECORDS.find((p) => p.provCode === codeStr);
  if (!province) {
    province = provincesData.RECORDS.find(
      (p) => p.provCode === codeStr.padStart(4, "0")
    );
  }
  return province ? province.provDesc : code;
};

const getCityName = (code) => {
  if (!code) return "";
  const codeStr = String(code).trim();
  let city = citiesData.RECORDS.find((c) => c.citymunCode === codeStr);
  if (!city) {
    city = citiesData.RECORDS.find(
      (c) => c.citymunCode === codeStr.padStart(6, "0")
    );
  }
  return city ? city.citymunDesc : code;
};

const getBarangayName = (code) => {
  if (!code) return "";
  const codeStr = String(code).trim();
  let barangay = barangaysData.RECORDS.find((b) => b.brgyCode === codeStr);
  if (!barangay) {
    barangay = barangaysData.RECORDS.find(
      (b) => b.brgyCode === codeStr.padStart(9, "0")
    );
  }
  return barangay ? barangay.brgyDesc : code;
};
</script>
