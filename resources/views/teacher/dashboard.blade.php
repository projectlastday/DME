@extends('layouts.app')

@section('title', 'Teacher dashboard')
@section('eyebrow', 'Teacher')
@section('page_title', 'Teacher dashboard')
@section('page_description', 'Move from the student roster into note creation and follow-up without leaving the shared teacher workflow.')
@section('page_actions')
    <a href="{{ route('teacher.students.index') }}" class="dme-nav__link">Browse students</a>
@endsection

@section('content')
    <div class="dme-stack">
        <section style="padding: 24px; border: 1px solid var(--surface-border); border-radius: 24px; background: rgba(255,255,255,0.56);">
            <x-teacher.page-header
                title="Teacher workflow"
                subtitle="Search for a student, open their detail page, then add or revise notes from the same shell."
                badge="Shared shell"
            />

            <div style="display: grid; gap: 14px; margin-top: 20px;">
                <div style="display: grid; gap: 8px;">
                    <h2 style="margin: 0; font-size: 1.05rem;">Start here</h2>
                    <p style="margin: 0; color: var(--muted); line-height: 1.7;">
                        Use the student roster to find a learner quickly, review grouped notes by date, and keep teacher-authored updates and image management in one place.
                    </p>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                    <a href="{{ route('teacher.students.index') }}" class="dme-nav__link">Open student roster</a>
                </div>
            </div>
        </section>
    </div>
@endsection
