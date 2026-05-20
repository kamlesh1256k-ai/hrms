<script>
$(function() {
    function loadCountries($container, done) {
        $.get('{{ route("employee.address.countries") }}', function(res) {
            var $sel = $container.find('.addr-country');
            $sel.find('option:not(:first)').remove();
            (res.countries || []).forEach(function(c) {
                $sel.append('<option value="' + c.id + '">' + c.name + '</option>');
            });
            if (done) done();
        });
    }
    function loadStates($container, countryId, done) {
        if (!countryId) {
            $container.find('.addr-state').html('<option value="">{{ __("Select State") }}</option>');
            $container.find('.addr-city').html('<option value="">{{ __("Select City") }}</option>');
            if (done) done();
            return;
        }
        $.get('{{ route("employee.address.states") }}', { country_id: countryId }, function(res) {
            var $sel = $container.find('.addr-state');
            $sel.find('option:not(:first)').remove();
            (res.states || []).forEach(function(s) {
                $sel.append('<option value="' + s.id + '">' + s.name + '</option>');
            });
            $container.find('.addr-city').html('<option value="">{{ __("Select City") }}</option>');
            if (done) done();
        });
    }
    function loadCities($container, stateId, done) {
        if (!stateId) {
            $container.find('.addr-city').html('<option value="">{{ __("Select City") }}</option>');
            if (done) done();
            return;
        }
        $.get('{{ route("employee.address.cities") }}', { state_id: stateId }, function(res) {
            var $sel = $container.find('.addr-city');
            $sel.find('option:not(:first)').remove();
            (res.cities || []).forEach(function(c) {
                $sel.append('<option value="' + c.id + '">' + c.name + '</option>');
            });
            if (done) done();
        });
    }
    function initAddrDropdowns($modal) {
        var $container = $modal.closest('.modal');
        if (!$container.find('.addr-country').length) return;
        loadCountries($container, function() {
            var $form = $container.find('form[data-addr-country]');
            var country = $form.length ? ($form.attr('data-addr-country') || '') : '';
            var state = $form.length ? ($form.attr('data-addr-state') || '') : '';
            var city = $form.length ? ($form.attr('data-addr-city') || '') : '';
            if (country) {
                $container.find('.addr-country').val(country);
                loadStates($container, country, function() {
                    if (state) {
                        $container.find('.addr-state').val(state);
                        loadCities($container, state, function() {
                            if (city) $container.find('.addr-city').val(city);
                        });
                    }
                });
            }
        });
        $container.off('change.addr', '.addr-country').on('change.addr', '.addr-country', function() {
            loadStates($container, $(this).val());
        });
        $container.off('change.addr', '.addr-state').on('change.addr', '.addr-state', function() {
            loadCities($container, $(this).val());
        });
    }
    $(document).on('shown.bs.modal', '.modal', function() {
        initAddrDropdowns($(this));
    });
});
</script>
