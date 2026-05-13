var LongActionProcess = (function () {
    
    var url = '';
    var process_id = '';
    var step_delay = 500;
    var rest_delay = 750;
    var timers_pull = [];
    var post_data = {};

    // List of callbacks
    var onCleanup,
        onReady,
        onProgress,
        onError,
        onWarning,
        onStart,
        onStop,
        onAlways;

    var clearAllTimers = function() {
        while (timers_pull.length > 0) {
            var timer_id = timers_pull.shift();
            if (timer_id) {
                clearTimeout(timer_id);
            }
        }
    };

    var cleanup = function () {
        var data = $.extend(true, {}, post_data);
        data.processId = process_id;
        data.cleanup = 1;
        $.post(
            url,
            data,
            function(r) {
                onCleanup && onCleanup(r);
            }).always(function() {
                clearAllTimers();
            });
    };

    var step = function(delay) {
        delay = delay || step_delay;
        var timer_id = setTimeout(function() {
            var data = $.extend(true, {}, post_data);
            data.processId = process_id;
            $.post(
                url,
                data,
                function(r) {
                    if (!r) {
                        step(rest_delay);
                    } else if (r.ready) {
                        onReady && onReady(r);
                        cleanup();
                    } else if (r.error) {
                        onError && onError(r);
                    } else if (r.progress) {
                        onProgress && onProgress(r);
                        step();
                    } else if (r.warning) {
                        onWarning && onWarning(r);
                        step();
                    } else {
                        step(rest_delay);
                    }
                    onAlways && onAlways(r);
                },
                'json'
            ).error(function() {
                step(rest_delay);
            });
        }, delay);
        timers_pull.push(timer_id);
    };

    var start = function() {
        onStart && onStart();
        var data = $.extend(true, {}, post_data);
        $.post(url, data,
            function(r) {
                if (r && r.processId) {
                    process_id = r.processId;
                    // invoke runner
                    step(100);
                    // invoke messenger
                    step(200);
                } else if (r && r.error) {
                    onError && onError(r);
                } else {
                    onError && onError('Server error');
                }
            }, 'json').error(function() {
                onError && onError('Server error');
            });
    };

    var stop = function() {
        onStop && onStop();
        clearAllTimers();
    };

    var LongActionProcess = function(options) {
        if (!options.url) {
            throw new Error("Url is required");
        }

        url = options.url;
        step_delay = options.step_delay || step_delay;
        rest_delay = options.rest_delay || rest_delay;
        post_data = options.post_data || post_data;

        // init callbacks
        onCleanup = options.onCleanup;
        onReady = options.onReady;
        onProgress = options.onProgress;
        onError = options.onError;
        onWarning = options.onWarning;
        onStart = options.onStart;
        onStop = options.onStop;
        onAlways = options.onAlways;

    };

    $.extend(LongActionProcess.prototype, {
        start: start,
        stop: stop
    });

    return LongActionProcess;

})();