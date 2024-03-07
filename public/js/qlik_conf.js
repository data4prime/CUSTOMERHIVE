$(document).ready(function () {
    // Adding change event listener to the select element with ID "mySelect"
    var type = $('[name="type"]').first();
    var type_val = type.val();
    var on_premise = ['qrsurl', 'endpoint', 'QRSCertfile', 'QRSCertkeyfile', 'QRSCertkeyfilePassword'];

    var saas = ['url', 'keyid', 'issuer', 'web_int_id', 'private_key'];
    if (type_val == 'On-Premise') {

        saas.forEach(element => {

            to_hide = document.getElementsByName(element);

            to_hide.forEach(hide => {
                hide.parentNode.style.display = 'none';

            });


        });

        on_premise.forEach(element => {

            to_show = document.getElementsByName(element);

            to_show.forEach(show => {
                show.parentNode.style.display = '';

            });


        });


    } else if (type_val == 'SAAS') {
        on_premise.forEach(element => {

            to_hide = document.getElementsByName(element);

            to_hide.forEach(hide => {
                hide.parentNode.style.display = 'none';

            });


        });

        saas.forEach(element => {

            to_show = document.getElementsByName(element);

            to_show.forEach(show => {
                show.parentNode.style.display = '';

            });


        });

    }

    type.change(function () {
        // Code to be executed when the value of the select changes
        var selectedValue = $(this).val();
        console.log("Selected value: " + selectedValue);
        var on_premise = ['qrsurl', 'endpoint', 'QRSCertfile', 'QRSCertkeyfile', 'QRSCertkeyfilePassword'];

        var saas = ['url', 'keyid', 'issuer', 'web_int_id', 'private_key'];

        //console.log(document.getElementsByName('type')[0]);

        //var type = document.getElementsByName('type')[0].value;

        var type = $('[name="type"]').first().val();

        if (type == 'On-Premise') {

            saas.forEach(element => {

                to_hide = document.getElementsByName(element);

                to_hide.forEach(hide => {
                    hide.parentNode.style.display = 'none';

                });


            });

            on_premise.forEach(element => {

                to_show = document.getElementsByName(element);

                to_show.forEach(show => {
                    show.parentNode.style.display = '';

                });


            });


        } else if (type == 'SAAS') {
            on_premise.forEach(element => {

                to_hide = document.getElementsByName(element);

                to_hide.forEach(hide => {
                    hide.parentNode.style.display = 'none';

                });


            });

            saas.forEach(element => {

                to_show = document.getElementsByName(element);

                to_show.forEach(show => {
                    show.parentNode.style.display = '';

                });


            });

        }

    });
});
