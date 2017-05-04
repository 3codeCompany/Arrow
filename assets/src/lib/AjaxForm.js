import {Observable} from 'rxjs/Observable';
import 'rxjs/add/observable/fromPromise';
import 'rxjs/add/observable/fromEvent';

class AjaxForm {

    constructor(element, action) {
        this.element = element;
        this.action = action
        this.events = {
            'beforeSerialize': [],
            'beforeSend': [],
            'success': [],
            'error': [],
            'validationError': [],
            'completed': [],
        }

        this.on('beforeSerialize', this.resetValidationErrors)
        this.on('beforeSerialize', this.cover)
        this.on('completed', this.uncover)
        this.on('validationError', this.displayValidationErrors);

    }

    init(context) {

        let result = context.querySelectorAll(".ajax-form");
        result.forEach(form => {

            Observable.fromEvent(form.querySelectorAll('.ajax-form-submit'), 'click')
                .subscribe(event => {
                    Observable.fromPromise($.post)
                    form.classList.add('ajax-form-load')
                })
            ;
        })

    }

    resetValidationErrors() {
        $(this.element).find(".field-error").removeClass('field-error');
        $(".field-error-addon").remove();
    }

    displayValidationErrors(data) {

        let first = true;
        Object.entries(data.fieldErrors).map(([name, errors]) => {
            let field = $(this.element).find("[name=\'" + name + "\']");
            if (field.length == 0) {
                field = $(this.element).find("[name=*\'[" + name + "]\']");
            }

            field.addClass('field-error');

            let position = field[0].getBoundingClientRect();
            $("<div></div>")
                .text(errors.join(", "))
                .addClass('field-error-addon')
                .css({
                    position: 'fixed',
                    top: position.top + position.height - 1,
                    left: position.left,
                })
                .width(position.width)
                .appendTo($("body"));
            if (first) {
                field.focus();
                first = false;
            }
        })
    }

    cover() {
        console.log('cokolwiek');
        console.log(this);
        $(this.element).css({opacity: 0.5, 'pointer-events': 'none'});
        console.log('cokolwiek 2');
    }

    uncover() {
        $(this.element).css({opacity: 1, 'pointer-events': 'auto'});
    }

    on(eventType, handler) {
        this.events[eventType].push(handler);
    }

    runEvent(eventType, data) {
        this.events[eventType].map(handler => {
            handler.apply(this, [data, {type: eventType}])
        })
    }

    debugError(error) {
        let errorWindow = window.open("", "", "width=800,height=600");
        errorWindow.document.write(error);
        errorWindow.focus();
    }

    submit() {
        this.runEvent('beforeSerialize', {});
        let inData = $(this.element).find("input,select,textarea").serialize();
        this.runEvent('beforeSend', {serialized: inData});
        $.post(this.action, inData)
            .done((data) => {
                try {
                    data = JSON.parse(data)
                    if (data.errors === undefined) {
                        this.runEvent('success', data);
                    } else {
                        this.runEvent('validationError', data);
                    }
                } catch (e) {
                    this.debugError(e.message + "<hr />" + data);
                    this.runEvent('error', data)
                }
            })
            .fail((exception) => {
                this.debugError(exception.message);
                this.runEvent('error', exception)
            })
            .always((data) => {
                this.runEvent('completed', data)
            });

    }

}

window.AjaxForm = AjaxForm;
document.addEventListener("DOMContentLoaded", function () {
    //window.AjaxForm  = new AjaxForm();
    //window.AjaxForm.init(document);

})