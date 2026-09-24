<div class="form-feedback">

    @if(session('success'))
        <div
            class="flash-message form-alert form-alert-success"
            data-auto-dismiss
            role="status"
        >
            <span class="form-alert-icon">
                <i class="fas fa-circle-check"></i>
            </span>

            <div class="form-alert-content">
                {{ session('success') }}
            </div>
        </div>
    @endif


    @if(session('error'))
        <div
            class="flash-message form-alert form-alert-error"
            data-auto-dismiss
            role="alert"
        >
            <span class="form-alert-icon">
                <i class="fas fa-circle-exclamation"></i>
            </span>

            <div class="form-alert-content">
                {{ session('error') }}
            </div>
        </div>
    @endif


    @if(session('warning'))
        <div
            class="flash-message form-alert form-alert-warning"
            data-auto-dismiss
            role="alert"
        >
            <span class="form-alert-icon">
                <i class="fas fa-triangle-exclamation"></i>
            </span>

            <div class="form-alert-content">
                {{ session('warning') }}
            </div>
        </div>
    @endif


    @if(session('info'))
        <div
            class="flash-message form-alert form-alert-info"
            data-auto-dismiss
            role="status"
        >
            <span class="form-alert-icon">
                <i class="fas fa-circle-info"></i>
            </span>

            <div class="form-alert-content">
                {{ session('info') }}
            </div>
        </div>
    @endif


    @if($errors->any())
        <div
            class="flash-message form-alert form-alert-error"
            data-auto-dismiss
            role="alert"
        >
            <span class="form-alert-icon">
                <i class="fas fa-circle-exclamation"></i>
            </span>

            <div class="form-alert-content">

                @if($errors->count() === 1)

                    {{ $errors->first() }}

                @else

                    <strong>
                        Please correct the following:
                    </strong>

                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                @endif

            </div>
        </div>
    @endif

</div>