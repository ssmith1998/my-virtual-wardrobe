<template>
    <q-page class="h-100">
        <div class="app-outfit-explorer-inner-wrapper">

            <div v-if="!showRecommendations" class="app-outfit-explorer-inner-wrapper__inner q-px-xl">
            <form @submit.prevent="handleSubmit" class="input-row-form">
                <div class="input-row">
                    <q-input
                        v-model="searchQuery"
                        label="Ask me to recommend an outfit"
                        :outlined="outlined"
                        filled
                        class="full-width-input"
                    >

                        <template v-slot:append>
                            <q-icon
                                v-if="searchQuery !== ''"
                                name="close"
                                @click="searchQuery = ''"
                                class="cursor-pointer"
                            />
                            <q-icon name="search" />
                        </template>

                        <template v-slot:hint> Field hint </template>
                    </q-input>

                    <div class="file-picker">
                        <q-btn dense flat round icon="attach_file" class="cursor-pointer" @click="() => fileInputRef && fileInputRef.click()" />
                        <span v-if="selectedFileCount > 0" class="file-count">{{ selectedFileCount }} file(s) selected</span>
                        <input ref="fileInputRef" type="file" style="display:none" @change="onFileChange" multiple />
                        <div v-if="selectedFilesError" class="file-error">{{ selectedFilesError }}</div>
                    </div>
                </div>
                <div class="submit-row q-mt-lg">
                    <q-btn type="submit" label="Generate Recommendations" color="primary" class="generate-btn" :disable="!searchQuery" />
                </div>
            </form>
            </div>

            <AppRecommendations
                v-else
                :recommendations="recommendations"
                :isLoading="isLoading"
                @back="() => { showRecommendations = false; recommendations = []; }"
            />
        </div>
    </q-page>

</template>
<script setup>
import { ref } from 'vue';
import {outfitRecommendation} from '../api/outfit-explorer.js';
import AppRecommendations from './AppRecommendations.vue';
const MAX_FILES = 2;
const searchQuery = ref('');
const fileInputRef = ref(null);
const selectedFiles = ref([]);
const selectedFileCount = ref(0);
const selectedFilesError = ref('');
// UI state
const recommendations = ref([]);
const isLoading = ref(false);
const showRecommendations = ref(false);

const handleSubmit = async () => {
    console.log('Form submitted with query:', searchQuery.value, 'file:', selectedFiles.value);
    const data = new FormData();
    for (let i = 0; i < selectedFiles.value.length; i++) {
        data.append('files[]', selectedFiles.value[i]);
    }
    data.append('prompt', searchQuery.value);

    isLoading.value = true;
    showRecommendations.value = true;
    try {
        const response = await outfitRecommendation(data);
        console.log('API response:', response);
        // Expecting response to be an array of recommendations
        recommendations.value = response || [];
    } catch (error) {
        console.error('Error fetching outfit recommendation:', error);
        recommendations.value = [];
    } finally {
        isLoading.value = false;
    }
};

function onFileChange(event) {
    const fList = event.target.files;
    const arr = fList ? Array.from(fList) : [];
    if (arr.length > MAX_FILES) {
        selectedFiles.value = arr.slice(0, MAX_FILES);
        selectedFileCount.value = selectedFiles.value.length;
        selectedFilesError.value = `Only ${MAX_FILES} files allowed — using first ${MAX_FILES}.`;
    } else {
        selectedFiles.value = arr;
        selectedFileCount.value = arr.length;
        selectedFilesError.value = '';
    }
}

</script>
<style scoped lang="scss">
.app-outfit-explorer-inner-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;

    form {
        width: 100%;
        max-width: none;
        padding: 0 16px; /* side padding */
        box-sizing: border-box;
    }

    &__inner {
        width: 100%;
        box-sizing: border-box;
    }
}

.full-width-input {
    flex: 1 1 auto;
}

.input-row {
    display: flex;
    gap: 8px;
    align-items: center;
}

.file-picker {
    display: flex;
    align-items: center;
    gap: 8px;
}

.file-name {
    font-size: 0.9rem;
    max-width: 160px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.file-error {
    color: #b0413e;
    font-size: 0.9rem;
    margin-top: 4px;
}

.generate-btn {
    width: 100%;
    min-width: unset;
}

/* Recommendation styles moved to AppRecommendations.vue */

</style>