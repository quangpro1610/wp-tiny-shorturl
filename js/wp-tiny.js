jQuery(document).ready(function($) {
    $('#shorten-form').submit(function(e) {
        e.preventDefault();
        if ($('#url').val() == '') {
            Swal.fire({
                icon: 'info',
                title: 'Information',
                text: 'Please enter your long url!',
                confirmButtonColor: '#008979'
            });
        } else {
            var alias = $('#alias').val();
            var url = $('#url').val();
            $.ajax({
                url: ajax_obj.ajax_url,
                method: "POST",
                data: {
                    action: 'short_link',
                    url: url,
                    alias: alias
                },
                success: function(res){
                    let result = JSON.parse(res);
                    let end = JSON.parse(result.data);

                    if (result.status == 'success') {
                        if (end.code == 0) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Your short link is created!',
                                text: end.data.tiny_url,
                                confirmButtonColor: '#008979'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: end.errors[0],
                                confirmButtonColor: '#008979'
                            })
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: result.data,
                            confirmButtonColor: '#008979'
                        })
                    }
                }
            });
        }
    });

});