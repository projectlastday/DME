import { buildStudentDetailHash, buildStudentsHash } from '../../core/router/buildHashRoute.js';
import { deriveStudentList } from '../students/deriveStudentList.js';

export function renderApp({ shell, state }) {
    shell.status.textContent = createStatusText(state);
    
    // Generate new HTML
    const newHTML = renderCurrentView(state);
    
    // Instead of completely destroying shell.view (which causes unrecoverable focus loss),
    // we do a lightweight DOM patch to only update what changed.
    updateDOM(shell.view, newHTML);
}

// A minimal, robust Vanilla JS DOM diffing algorithm
function updateDOM(domNode, htmlString) {
    const template = document.createElement('div');
    template.innerHTML = htmlString;
    
    function patchChildren(domParent, vdomParent) {
        const domChildren = Array.from(domParent.childNodes);
        const vChildren = Array.from(vdomParent.childNodes);

        // Remove extra DOM children at the end
        for (let i = domChildren.length - 1; i >= vChildren.length; i--) {
            domParent.removeChild(domChildren[i]);
        }

        // Patch or add new children
        for (let i = 0; i < vChildren.length; i++) {
            const vChild = vChildren[i];
            const domChild = domChildren[i];

            if (!domChild) {
                domParent.appendChild(vChild.cloneNode(true));
                continue;
            }

            // If node types or tags are completely different, replace the whole thing
            if (domChild.nodeType !== vChild.nodeType || domChild.tagName !== vChild.tagName) {
                domChild.replaceWith(vChild.cloneNode(true));
                continue;
            }

            // Update text nodes
            if (domChild.nodeType === Node.TEXT_NODE || domChild.nodeType === Node.COMMENT_NODE) {
                if (domChild.nodeValue !== vChild.nodeValue) {
                    domChild.nodeValue = vChild.nodeValue;
                }
                continue;
            }

            // Update element nodes
            if (domChild.nodeType === Node.ELEMENT_NODE) {
                const vAttrs = Array.from(vChild.attributes);
                const domAttrs = Array.from(domChild.attributes);

                // Remove attributes that no longer exist
                for (let j = 0; j < domAttrs.length; j++) {
                    const name = domAttrs[j].name;
                    if (!vChild.hasAttribute(name)) {
                        domChild.removeAttribute(name);
                    }
                }

                // Add or update attributes
                for (let j = 0; j < vAttrs.length; j++) {
                    const name = vAttrs[j].name;
                    const value = vAttrs[j].value;

                    // Skip the 'value' attribute if the user is actively typing in it
                    // This perfectly preserves the cursor offset & selection!
                    if (name === 'value' && domChild === document.activeElement) {
                        continue;
                    }

                    if (domChild.getAttribute(name) !== value) {
                        domChild.setAttribute(name, value);
                        
                        // Sync native property for controlled inputs
                        if (name === 'value' && 'value' in domChild && domChild !== document.activeElement) {
                            domChild.value = value;
                        }
                    }
                }

                // Recurse into the children
                patchChildren(domChild, vChild);
            }
        }
    }

    // Begin patching at the root shell.view (domNode) against the new template
    patchChildren(domNode, template);
}

function renderCurrentView(state) {
    if (state.currentView === 'fatal-db-error') return renderFatalDbError(state);

    if (state.currentView === 'boot') {
        return `
            <div class="flex flex-col items-center justify-center min-h-[60vh]">
                <div class="animate-pulse w-12 h-12 bg-amber-100 rounded-full mb-4"></div>
                <p class="text-slate-500 font-medium tracking-wide">Memulai aplikasi…</p>
            </div>
        `;
    }

    if (state.currentView === 'student-detail') {
        return renderDetailView(state);
    }

    return renderListView(state);
}

function renderListView(state) {
    const visibleStudents = deriveStudentList(state.routeData.students, state.searchQuery);
    
    let listMarkup = '';
    if (state.routeData.students.length === 0) {
        listMarkup = `
            <div class="text-center py-16 px-4">
                <div class="w-16 h-16 bg-slate-100 rounded-2xl mx-auto flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Belum ada murid</h3>
                <p class="text-slate-500">Tambahkan murid pertama Anda.</p>
            </div>
        `;
    } else if (visibleStudents.length === 0) {
        listMarkup = `
            <div class="text-center py-16 px-4">
                <h3 class="text-lg font-bold text-slate-900 mb-1">Tidak ditemukan</h3>
                <p class="text-slate-500">Tidak ada yang cocok dengan "${escapeHtml(state.searchQuery)}".</p>
            </div>
        `;
    } else {
        listMarkup = `
            <div class="grid grid-cols-3 gap-4">
                ${visibleStudents.map(student => `
                    <div class="group relative flex flex-col items-center justify-center p-4 bg-white rounded-2xl shadow-sm border border-slate-100 hover:border-amber-200 hover:shadow-md transition-all h-20 sm:h-24 active:scale-95">
                        <button class="absolute inset-0 w-full h-full cursor-pointer z-0" data-route-hash="${buildStudentDetailHash(student.id)}" type="button" aria-label="Buka murid"></button>
                        
                        <span class="relative z-10 pointer-events-none select-none font-bold text-slate-900 text-base sm:text-lg text-center w-full break-words px-2">
                            ${escapeHtml(student.name)}
                        </span>
                        
                        
                    </div>
                `).join('')}
            </div>
        `;
    }

    return `
        <div class="animate-fade-in pb-20">
            <header class="flex items-center justify-between mb-8 mt-2">
                <h1 class="font-heading text-3xl font-bold text-slate-900 tracking-tight">Daftar Murid</h1>
                <button class="bg-gradient-to-b from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-white shadow-md shadow-amber-500/20 px-5 flex items-center gap-2 h-11 rounded-full font-semibold transition-transform active:scale-95" data-action="open-add-student-dialog" type="button">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <span class="hidden sm:inline">Tambah</span>
                </button>
            </header>

            <div class="relative mb-6">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    class="w-full bg-white border border-slate-200 text-slate-900 rounded-2xl pl-11 pr-4 h-12 outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all shadow-sm"
                    data-search-input
                    type="search"
                    name="search"
                    value="${escapeHtml(state.searchQuery)}"
                    placeholder="Cari murid..."
                    autocomplete="off"
                >
            </div>

            ${listMarkup}
            ${renderAddStudentDialog(state)}
            ${renderRenameStudentDialog(state)}
            ${renderDeleteStudentDialog(state)}
            ${renderPhotoViewer(state)}
        </div>
    `;
}

function renderPhotoViewer(state) {
    const photoUrl = state.editState.activePhotoUrl;
    if (!photoUrl) return '';

    return `
        <div class="fixed inset-0 z-[100] flex items-center justify-center animate-fade-in">
            <button class="absolute inset-0 bg-slate-900/95 backdrop-blur-md" data-action="close-photo-viewer" type="button" aria-label="Tutup foto penuh"></button>
            <button class="absolute top-6 right-6 w-12 h-12 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center transition-colors z-[110]" data-action="close-photo-viewer" type="button" aria-label="Tutup foto penuh">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
            <div class="relative z-[105] w-full h-full p-4 sm:p-12 flex items-center justify-center">
                <img src="${escapeHtml(photoUrl)}" class="max-w-full max-h-full object-contain shadow-2xl rounded-lg animate-zoom-in" alt="Foto penuh">
            </div>
        </div>
    `;
}

function renderDetailView(state) {
    const selectedStudent = state.routeData.selectedStudent;

    if (!selectedStudent) {
        return `<div class="text-center py-20 text-slate-500">Rute murid tidak tersedia.</div>`;
    }

    const dateTabs = state.routeData.availableDates.map(date => {
        const isActive = state.selectedDateTab === date;
        return `
            <button
                class="whitespace-nowrap rounded-full px-5 py-2 text-sm font-semibold transition-all ${isActive ? 'bg-slate-900 text-white shadow-md shadow-slate-900/10' : 'bg-white text-slate-600 border border-slate-200 hover:border-amber-300'}"
                data-route-hash="${buildStudentDetailHash(selectedStudent.id, date)}"
                data-route-history="replace"
                type="button"
                aria-pressed="${String(isActive)}"
            >
                ${formatDateTab(date)}
            </button>
        `;
    }).join('');

    const timelineMarkup = state.routeData.selectedEntries.length === 0
        ? `
            <div class="text-center py-12 px-4 bg-white/50 rounded-3xl border border-dashed border-slate-200">
                <h3 class="text-slate-900 font-semibold mb-1">Belum ada catatan</h3>
                <p class="text-slate-500 text-sm">Tambahkan catatan atau foto pertama untuk murid ini.</p>
            </div>
        `
        : `
            <div class="space-y-6">
                ${state.routeData.selectedEntries.map(entry => `
                    <article class="bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-slate-400 font-medium text-sm flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                ${escapeHtml(formatDateTime(entry.createdAt))}
                            </span>
                            <div class="flex items-center gap-1">
                                <button class="text-slate-400 hover:text-amber-500 p-1.5 rounded-full hover:bg-amber-50 transition-colors" data-action="start-entry-edit" data-entry-id="${escapeHtml(entry.id)}" type="button" aria-label="Ubah"><svg class="w-4 h-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></button>
                                <button class="text-slate-400 hover:text-red-500 p-1.5 rounded-full hover:bg-red-50 transition-colors" data-action="open-delete-entry-dialog" data-entry-id="${escapeHtml(entry.id)}" data-entry-date="${escapeHtml(entry.date)}" type="button" aria-label="Hapus"><svg class="w-4 h-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                            </div>
                        </div>
                        
                        ${entry.note ? `<p class="w-full whitespace-pre-wrap [overflow-wrap:anywhere] break-words text-slate-800 leading-relaxed mb-4">${escapeHtml(entry.note)}</p>` : ''}
                        
                        ${entry.photos && entry.photos.length > 0 ? `
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-4">
                                ${entry.photos.map(photo => `
                                    <button class="relative aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-100 group/item cursor-zoom-in" data-action="open-photo-viewer" data-photo-url="${photo.fullPhotoUrl || photo.previewUrl || ''}" type="button">
                                        ${photo.previewUrl ? `
                                            <img src="${photo.previewUrl}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover/item:scale-110" alt="Foto">
                                        ` : `
                                            <div class="absolute inset-0 flex items-center justify-center text-xs text-slate-400 px-2 text-center pointer-events-none">${escapeHtml(photo.fileName || 'Foto')}</div>
                                        `}
                                    </button>
                                `).join('')}
                            </div>
                        ` : ''}
                    </article>
                `).join('')}
            </div>
        `;

    return `
        <div class="animate-fade-in pb-20">
            <!-- Sticky header -->
            <div class="sticky top-0 z-20 backdrop-blur-xl bg-[#FFFAF0]/80 pt-4 pb-4 -mx-4 px-4 sm:-mx-6 sm:px-6 border-b border-slate-200/50 mb-6 flex items-center gap-3">
                <button class="flex items-center justify-center w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-600 hover:text-amber-600 hover:border-amber-300 transition-all shadow-sm" data-route-hash="${buildStudentsHash()}" type="button" aria-label="Kembali">
                    <svg class="w-5 h-5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </button>
                <div class="flex-1 overflow-hidden">
                    <h1 class="font-heading text-xl sm:text-2xl font-bold text-slate-900 truncate">${escapeHtml(selectedStudent.name)}</h1>
                </div>
                <!-- Student Actions -->
                <div class="flex items-center gap-1">
                    <button class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-300 transition-all shadow-sm" data-action="open-rename-student-dialog" data-student-id="${escapeHtml(selectedStudent.id)}" data-student-name="${escapeHtml(selectedStudent.name)}" type="button" aria-label="Ubah nama"><svg class="w-4 h-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></button>
                    <button class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-300 transition-all shadow-sm" data-action="open-delete-student-dialog" data-student-id="${escapeHtml(selectedStudent.id)}" data-student-name="${escapeHtml(selectedStudent.name)}" type="button" aria-label="Hapus murid"><svg class="w-4 h-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                </div>
            </div>

            <!-- Composer (Create or Edit Mode) -->
            <div class="mb-10">
                ${renderEntryComposer(state)}
            </div>

            <!-- Timelines & Dates -->
            ${state.routeData.availableDates.length > 0 ? `
                <div class="flex overflow-x-auto hide-scrollbar gap-2 pb-2 mb-6 -mx-4 px-4 sm:mx-0 sm:px-0">
                    ${dateTabs}
                </div>
            ` : ''}
            
            ${timelineMarkup}

            ${renderRenameStudentDialog(state)}
            ${renderDeleteStudentDialog(state)}
            ${renderDeleteEntryDialog(state)}
            ${renderPhotoViewer(state)}
        </div>
    `;
}

function renderEntryComposer(state) {
    const isEditing = state.editState.isEditing;
    const isSaving = state.asyncFlags.isSavingEntry;
    const hasContent = (state.draftEntry.note ?? '').trim() !== '' || state.pendingPhotos.length > 0;
    const isExpanded = state.editState.isComposerExpanded || isEditing || hasContent;

    if (!isExpanded) {
        return `
            <div class="flex gap-3 animate-fade-in">
                <button 
                    class="group flex-1 flex items-center gap-3 px-4 py-3 bg-white rounded-2xl border border-slate-100 shadow-sm hover:border-amber-200 hover:shadow-md transition-all active:scale-[0.97]"
                    data-action="expand-composer"
                    data-mode="note"
                    type="button"
                >
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </div>
                    <span class="font-bold text-slate-800 text-sm">Catatan</span>
                </button>
                <button 
                    class="group flex-1 flex items-center gap-3 px-4 py-3 bg-white rounded-2xl border border-slate-100 shadow-sm hover:border-blue-200 hover:shadow-md transition-all active:scale-[0.97]"
                    data-action="expand-composer"
                    data-mode="photo"
                    type="button"
                >
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </div>
                    <span class="font-bold text-slate-800 text-sm">Gambar</span>
                </button>
            </div>
        `;
    }
    
    const errorsMarkup = state.editState.photoProcessingErrors.map(e => `
        <div class="bg-red-50 text-red-700 text-sm px-4 py-2 rounded-xl mb-3 flex items-start gap-2 border border-red-100">
            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div><strong>${escapeHtml(e.fileName)}:</strong> ${escapeHtml(e.message)}</div>
        </div>
    `).join('');

    const entryCreateErrorMarkup = state.editState.entryCreateError ? `
        <div class="text-sm text-red-600 mt-2 px-2">${escapeHtml(state.editState.entryCreateError)}</div>
    ` : '';

    return `
        <form data-entry-composer-form class="bg-white rounded-[2rem] p-2 sm:p-3 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-amber-200 ring-2 ring-amber-100 transition-all animate-slide-up">
            <textarea
                class="w-full bg-transparent border-none text-slate-800 placeholder-slate-400 p-4 min-h-[120px] outline-none resize-none leading-relaxed"
                data-entry-note-input
                name="entry_note"
                maxlength="5000"
                placeholder="${isEditing ? 'Ubah catatan...' : 'Tulis catatan baru...'}"
            >${escapeHtml(state.draftEntry.note ?? '')}</textarea>
            
            ${errorsMarkup}
            ${entryCreateErrorMarkup}
            
            ${renderRetainedEntryPhotos(state)}
            ${renderPendingPhotos(state)}

            <div class="flex items-center justify-between px-2 pb-2 pt-2 border-t border-slate-100 mt-2">
                <div class="flex items-center gap-2">
                    <label class="relative flex items-center justify-center w-10 h-10 bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-full cursor-pointer transition-colors" title="Tambah Foto">
                        <input
                            class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                            data-photo-input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            capture="environment"
                            multiple
                            aria-label="Tambah Foto"
                        >
                        ${state.asyncFlags.isProcessingPhotos 
                            ? `<svg class="animate-spin w-5 h-5 text-amber-600 pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`
                            : `<svg class="w-5 h-5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>`
                        }
                    </label>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-widest">${(state.draftEntry.note ?? '').length}/5000</span>
                </div>

                <div class="flex items-center gap-2">
                    ${!isEditing && !hasContent ? `
                        <button class="px-4 h-10 rounded-full font-semibold text-sm text-slate-500 hover:bg-slate-100 transition-colors" data-action="close-composer" type="button">Batal</button>
                    ` : ''}
                    ${isEditing ? `
                        <button class="px-4 h-10 rounded-full font-semibold text-sm text-slate-500 hover:bg-slate-100 transition-colors" data-action="cancel-entry-edit" type="button">Batal</button>
                    ` : ''}
                    <button class="bg-slate-900 hover:bg-slate-800 disabled:bg-slate-300 disabled:text-slate-500 text-white px-6 h-10 inline-flex items-center gap-2 rounded-full font-semibold text-sm transition-transform active:scale-95" type="submit" ${isSaving ? 'disabled' : ''}>
                        ${isSaving 
                            ? `<svg class="animate-spin w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...` 
                            : (isEditing ? 'Perbarui' : 'Simpan')
                        }
                    </button>
                </div>
            </div>
        </form>
    `;
}

function renderPendingPhotos(state) {
    if (state.pendingPhotos.length === 0) return '';
    
    return `
        <div class="flex gap-2 overflow-x-auto hide-scrollbar px-4 pb-4">
            ${state.pendingPhotos.map(photo => `
                <div class="relative w-20 h-20 shrink-0 rounded-2xl overflow-hidden group shadow-sm">
                    <img class="w-full h-full object-cover" src="${escapeHtml(photo.thumbnail.previewUrl)}" alt="">
                    <button class="absolute top-1 right-1 w-6 h-6 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center text-slate-700 hover:text-red-500 shadow-sm transition-colors text-xs" data-action="remove-pending-photo" data-photo-id="${escapeHtml(photo.id)}" type="button" aria-label="Hapus">✕</button>
                </div>
            `).join('')}
        </div>
    `;
}

function renderRetainedEntryPhotos(state) {
    if (!state.editState.isEditing || state.editState.retainedEntryPhotos.length === 0) return '';
    return `
        <div class="flex gap-2 overflow-x-auto hide-scrollbar px-4 pb-4">
            ${state.editState.retainedEntryPhotos.map(photo => `
                <div class="relative w-20 h-20 shrink-0 rounded-2xl overflow-hidden group border border-slate-200">
                    ${photo.previewUrl ? `<img class="w-full h-full object-cover opacity-80" src="${escapeHtml(photo.previewUrl)}" alt="">` : `<div class="w-full h-full bg-slate-100 flex items-center justify-center"><svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></div>`}
                    <button class="absolute top-1 right-1 w-6 h-6 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center text-slate-700 hover:text-red-500 shadow-sm transition-colors text-xs" data-action="remove-retained-entry-photo" data-photo-id="${escapeHtml(photo.id)}" type="button" aria-label="Hapus">✕</button>
                </div>
            `).join('')}
        </div>
    `;
}

// Minimalist Dialog Generators
function createDialogWrapper(isOpen, content) {
    if (!isOpen) return '';
    return `
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
            <div class="relative w-full sm:w-full sm:max-w-md bg-white rounded-[2rem] shadow-2xl animate-slide-up overflow-hidden p-6 sm:p-8">
                ${content}
            </div>
        </div>
    `;
}

function renderAddStudentDialog(state) {
    return createDialogWrapper(state.editState.addStudentDialogOpen, `
        <h3 class="font-heading text-2xl font-bold text-slate-900 mb-1 text-center">Murid Baru</h3>
        <p class="text-slate-500 mb-6 text-sm text-center">Tambahkan nama murid untuk memulai catatan.</p>
        <form data-add-student-form>
            <input class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-2xl px-4 h-14 outline-none focus:ring-2 focus:ring-amber-400 focus:bg-white transition-all mb-2 text-center" data-add-student-name-input type="text" name="student_name" value="${escapeHtml(state.editState.addStudentName)}" placeholder="Masukkan nama murid" autocomplete="off">
            ${state.editState.addStudentError ? `<p class="text-red-500 text-sm pl-2 mb-4">${escapeHtml(state.editState.addStudentError)}</p>` : '<div class="h-4"></div>'}
            <div class="flex gap-3 mt-4">
                <button class="flex-1 px-4 h-12 rounded-full font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors" data-action="close-add-student-dialog" type="button">Batal</button>
                <button class="flex-1 px-4 h-12 rounded-full font-semibold text-white bg-slate-900 hover:bg-slate-800 disabled:opacity-50 transition-colors" type="submit" ${state.asyncFlags.isCreatingStudent ? 'disabled' : ''}>${state.asyncFlags.isCreatingStudent ? 'Menyimpan...' : 'Simpan'}</button>
            </div>
        </form>
    `);
}

function renderRenameStudentDialog(state) {
    return createDialogWrapper(state.editState.renameStudentDialogOpen, `
        <h3 class="font-heading text-2xl font-bold text-slate-900 mb-1 text-center">Ubah Nama</h3>
        <p class="text-slate-500 mb-6 text-sm text-center">Perbarui nama untuk murid ini.</p>
        <form data-rename-student-form>
            <input class="w-full bg-slate-50 border border-slate-200 text-slate-900 rounded-2xl px-4 h-14 outline-none focus:ring-2 focus:ring-amber-400 focus:bg-white transition-all mb-2 text-center" data-rename-student-name-input type="text" name="rename_student_name" value="${escapeHtml(state.editState.renameStudentName)}" placeholder="Nama murid" autocomplete="off">
            ${state.editState.renameStudentError ? `<p class="text-red-500 text-sm pl-2 mb-4">${escapeHtml(state.editState.renameStudentError)}</p>` : '<div class="h-4"></div>'}
            <div class="flex gap-3 mt-4">
                <button class="flex-1 px-4 h-12 rounded-full font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors" data-action="close-rename-student-dialog" type="button">Batal</button>
                <button class="flex-1 px-4 h-12 rounded-full font-semibold text-white bg-slate-900 hover:bg-slate-800 disabled:opacity-50 transition-colors" type="submit" ${state.asyncFlags.isRenamingStudent ? 'disabled' : ''}>${state.asyncFlags.isRenamingStudent ? 'Menyimpan...' : 'Simpan'}</button>
            </div>
        </form>
    `);
}

function renderDeleteStudentDialog(state) {
    return createDialogWrapper(state.editState.deleteStudentDialogOpen, `
        <div class="w-14 h-14 rounded-full bg-red-100 text-red-600 flex items-center justify-center mb-5 mx-auto">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        </div>
        <h3 class="font-heading text-2xl font-bold text-slate-900 mb-2 text-center">Hapus murid ini?</h3>
        <p class="text-slate-600 mb-6 leading-relaxed text-center">Anda akan menghapus <strong class="text-slate-900">${escapeHtml(state.editState.deleteStudentName)}</strong> beserta semua catatan dan foto yang tersimpan. Tindakan ini permanen.</p>
        <form data-delete-student-form>
            ${state.editState.deleteStudentError ? `<p class="text-red-500 text-sm mb-4">${escapeHtml(state.editState.deleteStudentError)}</p>` : ''}
            <div class="flex gap-3 mt-4">
                <button class="flex-1 px-4 h-12 rounded-full font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors" data-action="close-delete-student-dialog" type="button">Batal</button>
                <button class="flex-1 px-4 h-12 rounded-full font-semibold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition-colors" type="submit" ${state.asyncFlags.isDeletingStudent ? 'disabled' : ''}>${state.asyncFlags.isDeletingStudent ? 'Menghapus...' : 'Hapus'}</button>
            </div>
        </form>
    `);
}

function renderDeleteEntryDialog(state) {
    return createDialogWrapper(state.editState.deleteEntryDialogOpen, `
        <div class="w-14 h-14 rounded-full bg-red-100 text-red-600 flex items-center justify-center mb-5 mx-auto">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
        </div>
        <h3 class="font-heading text-2xl font-bold text-slate-900 mb-2 text-center">Hapus catatan ini?</h3>
        <p class="text-slate-600 mb-6 leading-relaxed text-center">Catatan dan foto dalam entri ini akan dihapus secara permanen.</p>
        <form data-delete-entry-form>
            ${state.editState.deleteEntryError ? `<p class="text-red-500 text-sm mb-4">${escapeHtml(state.editState.deleteEntryError)}</p>` : ''}
            <div class="flex gap-3 mt-4">
                <button class="flex-1 px-4 h-12 rounded-full font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors" data-action="close-delete-entry-dialog" type="button">Batal</button>
                <button class="flex-1 px-4 h-12 rounded-full font-semibold text-white bg-red-600 hover:bg-red-700 transition-colors" type="submit">Hapus</button>
            </div>
        </form>
    `);
}

function renderFatalDbError(state) {
    return `
        <div class="flex flex-col items-center justify-center min-h-[80vh] px-4 text-center animate-fade-in">
            <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center text-red-500 mb-6">
                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
            <h2 class="font-heading text-3xl font-bold text-slate-900 mb-3">Penyimpanan Terkunci</h2>
            <p class="text-slate-500 max-w-sm mb-8 leading-relaxed">${escapeHtml(state.fatalError || 'Browser Anda tidak mengizinkan akses ke penyimpanan lokal (IndexedDB). Harap matikan private browsing.')}</p>
            <button class="bg-slate-900 text-white rounded-full px-8 h-12 font-semibold hover:bg-slate-800 transition-transform active:scale-95" data-action="retry-db-initialization" type="button">Coba Lagi</button>
        </div>
    `;
}

function createStatusText() {
    // Left intentionally empty to fulfill logic references but hide text from user.
    return '';
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function formatDateTime(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
}

function formatDateTab(dateString) {
    const parts = dateString.split('-');
    if (parts.length !== 3) return dateString;
    const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    if (Number.isNaN(date.getTime())) return dateString;
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' }).format(date);
}

function formatBytes(bytes) {
    if (bytes >= 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}
