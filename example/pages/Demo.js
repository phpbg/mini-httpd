new Vue({
    el: "#myFirstReactPhpApp",
    data: {
        apiUrl: "http://localhost:8080/api",
        tasks: null,
        newtask: null,
        ajaxError: null,
        disableForm: false
    },
    created: function() {
        this.resetTask();

        // Global AJAX configuration
        $.ajaxSetup({
            dataType : "json",
            timeout: 3000
        });

        // Global AJAX error handler
        var app = this;
        $(document).ajaxError(function (event, jqXHR) {
            if (jqXHR.responseJSON && jqXHR.responseJSON.message && jqXHR.responseJSON.message.length > 0) {
                app.ajaxError = "An error occurred : " + jqXHR.responseJSON.message;
            } else {
                app.ajaxError = "An error occurred. Please refresh page";
            }
        });

        $.ajax({
            url: app.apiUrl + "/task/get",
        }).done(function(tasks) {
            app.tasks = tasks;
        });
    },
    computed: {
        showWait: function() {
            return (! this.tasks && ! this.ajaxError);
        },
        showEarlyError: function() {
            return (! this.tasks && this.ajaxError);
        }
    },
    methods: {
        _addTask: function(url) {
            var app = this;
            app.disableForm = true;
            $.ajax({
                type: "POST",
                url: url,
                data: {task: app.newtask},
            }).done(function(tasks) {
                app.tasks = tasks;
                app.resetTask();
            }).always(function() {
                app.disableForm = false;
            });
        },
        addTaskAsync: function() {
            this._addTask(this.apiUrl + "/task/add-async");
        },
        addTask: function() {
            this._addTask(this.apiUrl + "/task/add");
        },
        resetTask: function() {
            this.newtask = '';
        }
    }
})