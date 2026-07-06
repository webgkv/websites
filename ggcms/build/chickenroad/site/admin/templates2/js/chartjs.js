'use strict';
document.addEventListener("DOMContentLoaded", function () {
	var colors = {
		primary: $('.colors .bg-primary').css('background-color'),
		primaryLight: $('.colors .bg-primary-bright').css('background-color'),
		secondary: $('.colors .bg-secondary').css('background-color'),
		secondaryLight: $('.colors .bg-secondary-bright').css('background-color'),
		info: $('.colors .bg-info').css('background-color'),
		infoLight: $('.colors .bg-info-bright').css('background-color'),
		success: $('.colors .bg-success').css('background-color'),
		successLight: $('.colors .bg-success-bright').css('background-color'),
		danger: $('.colors .bg-danger').css('background-color'),
		dangerLight: $('.colors .bg-danger-bright').css('background-color'),
		warning: $('.colors .bg-warning').css('background-color'),
		warningLight: $('.colors .bg-warning-bright').css('background-color'),
	};

	$(document).on('click','.js-statistics',function(){
	    var box = $('.statistics'),
            from = $('input[name*=from]',box).val(),
			to = $('input[name*=to]',box).val(),
			type = $('select[name*=type]',box).val(),
			user = $('input[name*=user]',box).val();
	    console.log(from);
		$.ajax({
			url: '/api/statistics/',
			type: 'POST',
			dataType: 'json',
			//contentType: false,
			//processData: false,
			data: {'from':from,'to':to,'user':user,'type':type},
			cache: false,
			beforeSend: function(){

			},
			success: function(json, textStatus){
			    if (json.html) {
			        $('.statistics_result').html(json.html);
					chartjs_user('chartjs');
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown){
				alert(errorThrown);
			},
			complete: function(XMLHttpRequest, textStatus){

			}
		});
		return false;
	});


    /*
    var js_chart = false;
	$(document).on('form.open','.form',function(){
		js_chart = true;
        //alert('2');
		//
		//chartjs_user();
	});

	/*
	$(document).on('show.bs.tab','.form a[data-toggle="tab"]', function (e) {
		//alert('show');
		var id = $(this).prop('id');
		if (id=='t2-tab') {
			//chartjs_user();
		}
	});

	$(document).on('shown.bs.tab','.form a[data-toggle="tab"]', function (e) {
        //alert('shown');
        var id = $(this).prop('id');
        if (id=='t2-tab') {
            //$('#t2').show();
            //alert($('#chartjs_user').height());
            if (js_chart == true) {
				$('.js_chart').each(function () {
					var id = $(this).attr('id');
					chartjs_user(id);
				})
			}
			js_chart = false;
		}
	});
	*/


    function chartjs_user(id) {
        var element = document.getElementById(id),
			labels = $(element).data('labels'),
            values = $('#'+id).data('values');
        //alert(labels);
        element.height = 50;
        new Chart(element, {
            //type: 'bar',
			ticks: { min: 0},
			type: 'horizontalBar',
            data: {
                labels: labels.split(','),
                datasets: [
                    {
                        backgroundColor: [
                            colors.primary,
                            colors.secondary,
                            colors.success,
                            colors.warning,
                            colors.info
                        ],
                        data: values
                    }
                ]
            },
			options: {
                legend: { display: false },
                title: {
                    display: false
                    //text: 'Predicted world population (millions) in 2050'
                }
            }
        });
    }

    function chartjs_three() {
        var element = document.getElementById("chartjs_three");
        element.height = 100;
        new Chart(element, {
            type: 'pie',
            data: {
                labels: ["Africa", "Asia", "Europe", "Latin America", "North America"],
                datasets: [{
                    label: "Population (millions)",
                    borderWidth: 5,
                    backgroundColor: [
                        colors.primary,
                        colors.secondary,
                        colors.success,
                        colors.warning,
                        colors.info
                    ],
                    data: [2478,3267,734,1784,933]
                }]
            },
            options: {
                title: {
                    display: true,
                    text: 'Predicted world population (millions) in 2050'
                }
            }
        });
    }

    function chartjs_four() {
        var element = document.getElementById("chartjs_four");
        element.height = 100;
        new Chart(element, {
            type: 'radar',
            data: {
                labels: ["Africa", "Asia", "Europe", "Latin America", "North America"],
                datasets: [
                    {
                        label: "1950",
                        fill: true,
                        backgroundColor: colors.primaryLight,
                        borderColor: colors.primary,
                        pointBorderColor: "#fff",
                        data: [8.77,55.61,21.69,6.62,6.82]
                    }, {
                        label: "2050",
                        fill: true,
                        backgroundColor: colors.successLight,
                        borderColor: colors.success,
                        pointBorderColor: "#fff",
                        data: [25.48,54.16,7.61,8.06,4.45]
                    }
                ]
            },
            options: {
                title: {
                    display: true,
                    text: 'Distribution in % of world population'
                }
            }
        });
    }

    function chartjs_five(id) {
        var element = document.getElementById("chartjs_five");
        element.height = 100;
        new Chart(element, {
            type: 'horizontalBar',
            data: {
                labels: ["Africa", "Asia", "Europe", "Latin America", "North America"],
                datasets: [
                    {
                        label: "Population (millions)",
                        backgroundColor: [
                            colors.primary,
                            colors.secondary,
                            colors.success,
                            colors.warning,
                            colors.info
                        ],
                        data: [2478,5267,734,784,433]
                    }
                ]
            },
            options: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Predicted world population (millions) in 2050'
                }
            }
        });
    }

    function chartjs_six() {
        var element = document.getElementById("chartjs_six");
        element.height = 100;
        new Chart(element, {
            type: 'bar',
            data: {
                labels: ["1900", "1950", "1999", "2050"],
                datasets: [{
                    label: "Europe",
                    type: "line",
                    borderColor: colors.warning,
                    data: [408,547,675,734],
                    fill: false
                }, {
                    label: "Africa",
                    type: "line",
                    borderColor: colors.success,
                    data: [133,221,783,2478],
                    fill: false
                }, {
                    label: "Europe",
                    type: "bar",
                    backgroundColor: colors.secondary,
                    data: [408,547,675,734],
                }, {
                    label: "Africa",
                    type: "bar",
                    backgroundColor: colors.primary,
                    backgroundColorHover: "#3e95cd",
                    data: [133,221,783,2478]
                }
                ]
            },
            options: {
                title: {
                    display: true,
                    text: 'Population growth (millions): Europe & Africa'
                },
                legend: { display: false }
            }
        });
    }

});