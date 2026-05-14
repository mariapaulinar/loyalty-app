{{--
 Reward Loyalty - Proprietary Software
 Copyright (c) 2025 NowSquare. All rights reserved.
 See LICENSE file for terms.

 CSV Import System

 Purpose: Beautiful drag & drop CSV import for voucher codes.
 Design matches partner analytics pages with breadcrumbs and consistent styling.
--}}

@extends('partner.layouts.default')

@section('page_title', trans('common.import_vouchers') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
    
    {{-- Page Header --}}
    <x-ui.page-header
        icon="upload"
        :title="trans('common.import_codes')"
        :description="trans('common.import_codes_description')"
        :breadcrumbs="[
            ['url' => route('partner.index'), 'icon' => 'home', 'title' => trans('common.dashboard')],
            ['url' => route('partner.vouchers.batches'), 'text' => trans('common.batches')],
            ['text' => trans('common.import_codes')]
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('partner.vouchers.batches') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 
                       text-sm font-medium text-secondary-700 dark:text-secondary-300 
                       bg-white dark:bg-secondary-800 
                       border border-secondary-200 dark:border-secondary-700 
                       rounded-xl shadow-sm
                       hover:bg-secondary-50 dark:hover:bg-secondary-700 
                       hover:border-secondary-300 dark:hover:border-secondary-600
                       focus:outline-none focus:ring-2 focus:ring-primary-500/20
                       transition-colors duration-200">
                <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                <span class="hidden sm:inline">{{ trans('common.back_to_batches') }}</span>
            </a>
        </x-slot>
    </x-ui.page-header>

    <div x-data="csvImport()" class="space-y-6">
        {{-- CSV Format Guide --}}
        <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm p-6">
                <div class="flex items-start gap-4">
                    <x-ui.icon icon="file-text" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-secondary-900 dark:text-white mb-2">
                            {{ trans('common.csv_format_requirements') }}
                        </h3>
                        <p class="text-sm text-secondary-600 dark:text-secondary-400 mb-4">
                            {{ trans('common.csv_format_description') }}
                        </p>
                        
                        {{-- Example CSV --}}
                        <div class="bg-secondary-900 dark:bg-black rounded-xl p-4 font-mono text-sm text-emerald-400 overflow-x-auto">
                            <div class="text-secondary-500">code,type,value,valid_until,max_uses_per_member</div>
                            <div>WELCOME10,fixed_amount,10.00,2025-12-31,1</div>
                            <div>FLASH20,percentage,20,2025-12-15,3</div>
                            <div>SUMMER25,percentage,25,2025-09-30,1</div>
                        </div>

                        <div class="mt-4">
                            <button
                                type="button"
                                @click="downloadTemplate()"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-secondary-700 bg-white border border-secondary-300 rounded-xl hover:bg-secondary-50 transition-all"
                            >
                                <x-ui.icon icon="download" class="w-4 h-4 mr-2" />
                                {{ trans('common.download_template') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Upload Area --}}
            <div class="bg-white dark:bg-secondary-900 rounded-2xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-8">
                <form @submit.prevent="submitImport()" enctype="multipart/form-data">
                    {{-- Club Selection --}}
                    <div class="mb-8">
                        <x-forms.select
                            name="club_id"
                            :label="trans('common.select_club')"
                            :placeholder="trans('common.choose_club')"
                            :options="$clubs->pluck('name', 'id')->toArray()"
                            :required="true"
                            x-model="clubId"
                        />
                    </div>

                    {{-- File Upload Component --}}
                    <x-forms.file
                        name="file"
                        accept=".csv"
                        icon="upload-cloud"
                        :placeholder="trans('common.drag_drop_csv')"
                        :requirements="trans('common.max_file_size_10mb')"
                        @change="handleFileSelect($event)"
                        x-ref="fileInput"
                    />

                    {{-- Options --}}
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-forms.switch
                            name="dry_run"
                            :label="trans('common.dry_run_mode')"
                            :description="trans('common.validate_without_importing')"
                            checked
                            alpine="dryRun"
                        />

                        <x-forms.switch
                            name="skip_duplicates"
                            :label="trans('common.skip_duplicates')"
                            :description="trans('common.ignore_existing_codes')"
                            checked
                            alpine="skipDuplicates"
                        />
                    </div>

                    {{-- Submit Button --}}
                    <div class="mt-8 flex items-center gap-4">
                        <button
                            type="submit"
                            x-bind:disabled="!clubId || loading || !$refs.fileInput?.files[0]"
                            class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-primary-600 rounded-xl hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <x-ui.icon icon="upload" class="w-4 h-4 mr-2" />
                            <span x-show="!loading">{{ trans('common.import_vouchers') }}</span>
                            <span x-show="loading" x-cloak>{{ trans('common.processing') }}...</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Preview Results --}}
            <div x-show="preview.length > 0" x-transition class="space-y-6" x-cloak>
                <div class="bg-white dark:bg-secondary-900 rounded-2xl shadow-sm border border-secondary-200 dark:border-secondary-700 p-6">
                    <div class="flex items-start gap-4 mb-6">
                        <x-ui.icon icon="eye" class="w-5 h-5 text-secondary-400 dark:text-secondary-500" />
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-secondary-900 dark:text-white mb-2">
                                {{ trans('common.import_preview') }}
                            </h3>
                            <div class="flex items-center gap-6 text-sm">
                                <span class="text-emerald-600 dark:text-emerald-400">
                                    <strong x-text="importResult.imported"></strong> {{ trans('common.will_be_imported') }}
                                </span>
                                <span class="text-amber-600 dark:text-amber-400">
                                    <strong x-text="importResult.skipped"></strong> {{ trans('common.will_be_skipped') }}
                                </span>
                                <span class="text-red-600 dark:text-red-400" x-show="importResult.errors.length > 0" x-cloak>
                                    <strong x-text="importResult.errors.length"></strong> {{ trans('common.errors') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Preview Table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-xs uppercase text-secondary-600 dark:text-secondary-400 bg-secondary-50 dark:bg-secondary-800/50">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ trans('common.line') }}</th>
                                    <th class="px-4 py-3 text-left">{{ trans('common.code') }}</th>
                                    <th class="px-4 py-3 text-left">{{ trans('common.type') }}</th>
                                    <th class="px-4 py-3 text-left">{{ trans('common.value') }}</th>
                                    <th class="px-4 py-3 text-left">{{ trans('common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary-200 dark:divide-secondary-700">
                                <template x-for="item in preview" :key="item.line">
                                    <tr class="hover:bg-secondary-50 dark:hover:bg-secondary-800/50">
                                        <td class="px-4 py-3" x-text="item.line"></td>
                                        <td class="px-4 py-3 font-mono" x-text="item.code"></td>
                                        <td class="px-4 py-3 capitalize" x-text="item.type"></td>
                                        <td class="px-4 py-3" x-text="item.value"></td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="px-2 py-1 rounded-full text-xs font-medium"
                                                :class="{
                                                    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300': item.status === 'ready',
                                                    'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300': item.status === 'skipped'
                                                }"
                                                x-text="item.status"
                                            ></span>
                                            <span x-show="item.reason" class="ml-2 text-xs text-secondary-600 dark:text-secondary-400" x-text="item.reason" x-cloak></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    {{-- Errors --}}
                    <div x-show="importResult.errors.length > 0" class="mt-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4" x-cloak>
                        <h4 class="font-semibold text-red-900 dark:text-red-100 mb-2 flex items-center gap-2">
                            <x-ui.icon icon="alert-circle" class="w-5 h-5" />
                            {{ trans('common.import_errors') }}
                        </h4>
                        <ul class="list-disc list-inside space-y-1 text-sm text-red-800 dark:text-red-200">
                            <template x-for="error in importResult.errors" :key="error">
                                <li x-text="error"></li>
                            </template>
                        </ul>
                    </div>

                    {{-- Confirm Import Button --}}
                    <div x-show="dryRun && importResult.success && importResult.imported > 0" class="mt-6 pt-6 border-t border-secondary-200 dark:border-secondary-700" x-cloak>
                        <button
                            type="button"
                            @click="confirmImport()"
                            class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-primary-600 rounded-xl hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 transition-colors duration-200"
                        >
                            <x-ui.icon icon="check-circle" class="w-4 h-4 mr-2" />
                            {{ trans('common.confirm_import') }} (<span x-text="importResult.imported"></span> {{ trans('common.codes') }})
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function csvImport() {
    return {
        clubId: '',
        dryRun: true,
        skipDuplicates: true,
        loading: false,
        preview: [],
        importResult: {
            success: false,
            imported: 0,
            skipped: 0,
            errors: [],
            dry_run: true
        },
        
        handleFileSelect(event) {
            // File selection is handled by the component
        },
        
        async submitImport() {
            const fileInput = this.$refs.fileInput;
            const file = fileInput?.files[0];
            
            if (!file || !this.clubId) return;
            
            this.loading = true;
            const formData = new FormData();
            formData.append('file', file);
            formData.append('club_id', this.clubId);
            formData.append('dry_run', this.dryRun);
            formData.append('skip_duplicates', this.skipDuplicates);
            
            try {
                const response = await fetch('{{ route('partner.vouchers.import.process') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                
                const result = await response.json();
                this.importResult = result;
                this.preview = result.preview || [];
                
                if (!this.dryRun && result.success) {
                    window.location.href = '{{ route('partner.vouchers.batches') }}';
                }
            } catch (error) {
                alert('{{ trans('common.import_error') }}: ' + error.message);
            } finally {
                this.loading = false;
            }
        },
        
        confirmImport() {
            this.dryRun = false;
            this.submitImport();
        },
        
        downloadTemplate() {
            const csv = 'code,type,value,valid_until,max_uses_per_member\nWELCOME10,fixed_amount,10.00,2025-12-31,1\nFLASH20,percentage,20,2025-12-15,3';
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'voucher-import-template.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    }
}
</script>
@endpush
@endsection
