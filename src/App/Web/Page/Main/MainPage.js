// Icons
feather.replace();

// datepicker
function setDatepicker(selector) {
    $(selector).daterangepicker({
        autoApply: true,
        timePicker: false,
        timePicker24Hour: false,
        ranges: {
            'Сегодня': [moment(), moment()],
            'Вчера': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            '7 дней': [moment().subtract(6, 'days'), moment()],
            '30 дней': [moment().subtract(29, 'days'), moment()],
            'Этот месяц': [moment().startOf('month'), moment().endOf('month')],
            'Прошлый месяц': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        locale: {
            format: 'DD.MM.YYYY',
            separator: ' - ',
            applyLabel: 'ОК',
            cancelLabel: 'Отмена',
            fromLabel: 'С',
            toLabel: 'До',
            customRangeLabel: 'Custom',
            weekLabel: 'W',
            daysOfWeek: [
                'Вс',
                'Пн',
                'Вт',
                'Ср',
                'Чт',
                'Пт',
                'Сб'
            ],
            monthNames: [
                'Январь',
                'Февраль',
                'Март',
                'Апрель',
                'Май',
                'Июнь',
                'Июль',
                'Август',
                'Сентябрь',
                'Октябрь',
                'Ноябрь',
                'Декабрь'
            ],
            firstDay: 1
        },
        showCustomRangeLabel: false,
        alwaysShowCalendars: true,
        // startDate: moment().format('MM.DD.YYYY'),
        // endDate: moment().format('MM.DD.YYYY'),
        opens: 'left',
    }, function(start, end, label) {
        var forInput = this.element.attr('data-for');
        $('input[name="' + forInput + '[start]"]').val(start.format('YYYY-MM-DD'));
        $('input[name="' + forInput + '[end]"]').val(end.format('YYYY-MM-DD'));
        console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')');
    });
}
setDatepicker('.period-pick');

// tabs
// из-за 2х списков нужно синхронизировать их по показу и скрытию
$('#pills-tab a').on('click', function(e) {
    $('#pills-main-tab a').removeClass('active').removeClass('show').attr('aria-selected', false);
    $('#pills-main-tabContent .tab-pane').removeClass('active').removeClass('show');
});
$('#pills-main-tab a').on('click', function(e) {
    $('#pills-tab a').removeClass('active').removeClass('show').attr('aria-selected', false);
    $('#pills-tabContent .tab-pane').removeClass('active').removeClass('show');
});

// add tabs
window.slideCount = 0;
$('.js-addSlide').on('click', function(e) {
    e.preventDefault();
    window.slideCount += 1;
    var id = 'pills-slide-' + window.slideCount;

    var tab = $('#pillTabContentTemplate').clone(true, true);
    tab
        .removeClass('d-none')
        .attr('id', id).attr('aria-labelledby', id + '-tab')
        .html(tab.html().replace(/%slideId%/g, window.slideCount))
        .appendTo('#pills-tabContent');
    setDatepicker('#' + tab.attr('id') + ' .period-pick');
    $('.js-addDiagram', '#' + tab.attr('id')).click();

    var li = $('#pillTabTemplate').clone(true, true);
    li.removeClass('d-none').removeAttr('id');
    $('a', li)
        .attr('href', '#' + id)
        .attr('id', id + '-tab')
        .attr('aria-controls', id)
        .html($('a', li).html().replace(/pillTabTemplate/g, 'Слайд ' + window.slideCount))
        .on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
    li.appendTo('#pills-tab');
});

// remove tabs
$('body').on('click', '.js-remove-slide', function(e) {
    e.preventDefault();
    var slidePill = $(this).prev('a').attr('aria-controls');
    $(this).parents('.nav-item').eq(0).remove();
    $('#' + slidePill).remove();
});

// add diagram
$('body').on('click', '.js-addDiagram', function(e) {
    e.preventDefault();
    var diagramGroupId = $(this).data('diagramGroupId');
    var lastDiagramId = $(this).data('diagramLastId') || 0;
    var diagramId = lastDiagramId + 1;

    $(this).data('diagramLastId', diagramId);

    var diagramBlock = $('.diagramTemplate-' + diagramGroupId).clone(true, true);
    diagramBlock.removeClass('d-none').removeClass('diagramTemplate-' + diagramGroupId);
    diagramBlock.html(diagramBlock.html().replace(/%diagramId%/g, diagramId));
    diagramBlock.appendTo('.diagramGroup-' + diagramGroupId);
});

// remove diagrams
$('body').on('click', '.js-remove-diagram', function(e) {
    e.preventDefault();
    $(this).parents('.js-diagram').eq(0).remove();
});

// form submitting
function checkAndPost(form) {
    if (!$('select[name="topicId"]').val()) {
        $('#modal-form-error').modal('show');
    } else {
        form.submit();
    }
}

// set available only correct diagram types
function onDiagramSelect(select) {
    if (!select.value) {
        return;
    }

    var availableDiagramTypes = window.jsData.diagramTypes[select.value];
    var diagramId = $(select).attr('id').replace('inputGroupDiagramSection-', '');
    var typeSelect = 'inputGroupDiagramType-' + diagramId;

    $('option', '#' + typeSelect).each(function(index, element) {
        if (index == 0) {
            return;
        }
        if (availableDiagramTypes.indexOf(element.value) < 0) {
            $(element).prop('disabled', true);
        } else {
            $(element).prop('disabled', false);
        }
    });

    var wrapper = $(select).parents('.form-row').eq(0);

    // для тегов еще кое-что делаем
    if (select.value === 'tags.byTime' || select.value === 'tags.sentiment') {
        if ($('.js-diagramTags', wrapper).length === 0) {
            // если еще нет тегов
            jQuery.ajax({
                method: 'post',
                data: {
                    _ajax: 'tags',
                    diagramId: diagramId,
                    form: $(select).parents('form').eq(0).serialize(),
                },
                success: function(data, textStatus, jqXHR) {
                    if (data.tags) {
                        wrapper.append(data.tags);
                    }
                },
            });
        }
    } else {
        $('.js-diagramTags', wrapper).remove();
    }
}
