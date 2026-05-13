if (!window.shopSkcatimageSettings) {

    var shopSkcatimageSettings = (function ($) {

        'use strict';

        var shopSkcatimageSettings = function (params) {

            this.init(params);

        };

        shopSkcatimageSettings.prototype = {

            _config: {
                "container": ".js-skcatimage"
            },

            /**
             * Инициализация объекта
             * @method init
             *
             * @param {Object} params Параметры
             *
             */
            init: function (params) {

                var that = this;

                that.params = $.extend({}, that._config, params);

                that.process = false;

                that.initElements();

                that.onSettingsSave();

                that.onChangeInput();

                that.onAddRow();

                that.onDelete();

                that.onRefresh();

            },

            initElements: function () {
                var that = this,
                    elements = {};

                elements.container = $(that.params.container);
                elements.groupsForm = elements.container.find(".js-skcatimage-groups-form");
                elements.groupsBody = elements.container.find(".js-skcatimage-groups-body");
                elements.groupsItems = elements.groupsForm.find(".js-skcatimage-groups-item");
                elements.groupsInput = elements.groupsForm.find("input[type=text]");
                elements.groupsInputErrors = elements.groupsForm.find(".js-error-msg");
                elements.groupsAdd = elements.container.find(".js-skcatimage-groups-add");
                elements.settingsForm = elements.container.find(".js-skcatimage-settings-form");
                elements.settingsControls = elements.container.find("select, input");

                that.elements = elements;

            },

            onChangeInput: function () {
                var that = this;

                that.elements.groupsForm.on("change", "input[type=text]", function () {

                    that.elements.groupsInput.removeClass("error");
                    that.elements.groupsInputErrors.text("");

                    that.save();

                });

            },

            onAddRow: function () {
                var that = this;

                that.elements.groupsAdd.on("click", function () {

                    that.params.max_id++;

                    $.post("?plugin=skcatimage&action=groupsAdd", {max_id: that.params.max_id}, function (resp) {
                        if (resp.status == "ok") {
                            that.elements.groupsBody.append(resp.data.html);
                            that.initElements();
                        }
                    }, "json")
                });
            },

            onDelete: function () {
                var that = this;

                that.elements.groupsForm.on("click", ".js-skcatimage-groups-delete", function () {
                    if (confirm("Вы уверены, что хотите удалить группу? Она больше не будет доступна для использования в шаблонах.")) {
                        $(this).closest(".js-skcatimage-groups-item").detach();
                        that.save();
                    }
                })
            },

            save: function () {
                var that = this;

                $.post("?plugin=skcatimage&action=groupsSave", that.elements.groupsForm.serialize(), function (resp) {

                    if (resp.status == "fail") {

                        $.each(resp.errors.input, function (id, inputs) {
                            $.each(inputs, function (input, value) {
                                that.elements.groupsInput
                                    .filter("[data-id='" + id + "']")
                                    .filter("[data-name='" + input + "']")
                                    .addClass("error")
                                    .parent().find(".js-error-msg").text(value)
                            })
                        });

                    } else if (resp.status == "ok") {

                    } else {
                        alert("Неизвестная ошибка");
                    }

                }, "json")
            },

            onSettingsSave: function () {
                var that = this,
                    elements = that.elements,
                    form = elements.settingsForm;

                elements.settingsControls.on("change", function () {

                    $.post(form.attr("action"), form.serialize(), function () {
                    });

                })
            },

            onRefresh: function () {
                var that = this,
                    elements = that.elements;

                that.elements.groupsForm.on("click", ".js-skcatimage-groups-refresh", function () {
                    var element = $(this),
                        groupName = element.closest(".js-skcatimage-groups-item").find(".js-skcatimage-groups-input-id").val();

                    if (!groupName) {
                        alert("Задайте идентификатор для категории");
                        return false;
                    }

                    if (!confirm("Вы уверены, что хотите обновить эскзизы изображений для категории с идентификатором: " + groupName + ". Не закрывайте страницу, пока счетчик не дойдет до 100%.")) {
                        return false;
                    }

                    that.process = true;

                    var url = "?plugin=skcatimage&action=groupsRefresh",
                        processId,
                        errorLabel = false,
                        $status = element.parent().find(".js-skcatimage-groups-percent");

                    var cleanup = function () {
                        $.post(url, {processId: processId, cleanup: 1, groupName: groupName}, function (r) {
                            that.process = false;
                        }, 'json');
                    };

                    var msgProcess = function (msg) {
                        $status.html(msg)
                    };

                    var step = function (delay) {
                        delay = delay || 2000;
                        var timer_id = setTimeout(function () {
                            $.post(url, {processId: processId, groupName: groupName},
                                function (r) {
                                    if (errorLabel) {
                                        return;
                                    } else if (!r) {
                                        step(3000);
                                    } else if (r && r.ready) {
                                        msgProcess("Завершено");
                                        cleanup();
                                    } else if (r && r.error) {
                                        alert(r.error);
                                        msgProcess("");
                                        errorLabel = true;
                                        cleanup();
                                    } else {
                                        if (r && r.progress) {
                                            msgProcess(r.progress + "%");
                                        }
                                        step();
                                    }
                                },
                                'json').error(function () {
                                step(3000);
                            });
                        }, delay);
                    };


                    $.post(url, {groupName: groupName}, function (r) {
                        if (r && r.processId) {
                            processId = r.processId;
                            msgProcess("0%");
                            step(1000);   // invoke Runner
                            step();         // invoke Messenger
                        } else if (r && r.error) {
                            alert(r.error);
                        } else {
                            alert("Серверная ошибка");
                            msgProcess("");
                        }
                    }, 'json').error(function () {
                        alert("Серверная ошибка");
                        msgProcess("");
                    });

                });

            }

        };

        return shopSkcatimageSettings;

    })(jQuery);
}
