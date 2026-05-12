$( document ).ready(function() {

        $(".changeNow").on('click', function() {
          $('#post_21_gamestart').val('2025-09-26 19:00'); 
          $('#post_21_gamestart').trigger("change");
        })

        $(".clsgamestart").on('change', function(){
          var dat = new Date($(this).val());
          var start = Math.abs(1.5);
          // console.log(start);
          // if (start<0) start = -start;
          var end = 2.5;
          var H_ = 60000 * 60;
          var startT = new Date(dat.getTime() - (H_ * start));
          var endT = new Date(dat.getTime() + (H_ * end));
          $('#startdate').val(startT.toLocaleString().substring(0, 16));
          $('#stopdate').val(endT.toLocaleString().substring(0, 16));
        });


        $(".clsFrmgamestart").on('change', function(){
          var id = $(this).data('id');
          console.log('go', id);
          var dat = new Date($(this).val());
          var start = Math.abs($('#pass_start').val());
          var end = Math.abs($('#pass_slut').val());
          var H_ = 60000 * 60;
          var startT = new Date(dat.getTime() - (H_ * start));
          var endT = new Date(dat.getTime() + (H_ * end));
          $('#post_' + id + '_startdate').val(startT.toLocaleString().substring(0, 16));
          $('#post_' + id + '_stopdate').val(endT.toLocaleString().substring(0, 16));
          $('#post_' + id + '_edited').prop( "checked", true );

          var j = JSON.parse($('#data').val());
          // console.log(j);
          // return;
          // BUG fortsätt att bygga på testfunktionen
          var data = $('#changeWWW').serializeArray().reduce(function(obj, item) {
              var dta = item.name.replaceAll(']',' ').replaceAll('[','').trim().split(' ');
              if (dta.length == 2) {
                var kx = dta[0];
                var vx = dta[1];
                j[kx][vx] = item.value;
              }
              return obj;
          }, {});
          console.log(j);

          // console.log(data);
          return;

          var formData = $('#changeWWW').serializeArray();
          console.log(formData);

          var object = {};
          formData.forEach(function(value, key){
              object[key] = value;
          });
          var json = JSON.stringify(object);
          console.log(json);
          
          // var object = {};
          // formData.forEach((value, key) => object[key] = value);
          // var json = JSON.stringify(object);
        });



      });