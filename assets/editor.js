(function (wp) {
  if (!wp || !wp.plugins || !wp.editPost || !wp.components || !wp.data || !wp.element) return;

  const el = wp.element.createElement;
  const PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
  const TextControl = wp.components.TextControl;
  const ToggleControl = wp.components.ToggleControl;
  const useSelect = wp.data.useSelect;
  const useDispatch = wp.data.useDispatch;

  function pad(n) { return String(n).padStart(2, '0'); }

  function dateFromTimestamp(ts) {
    ts = parseInt(ts || 0, 10);
    if (!ts) return '';
    const d = new Date(ts * 1000);
    return d.getUTCFullYear() + '-' + pad(d.getUTCMonth() + 1) + '-' + pad(d.getUTCDate());
  }

  function time24(value) {
    if (!value) return '';
    const m = String(value).match(/^(\d{1,2}):(\d{2})(?:\s*(AM|PM))?$/i);
    if (!m) return value;
    let h = parseInt(m[1], 10);
    const min = m[2];
    const ap = (m[3] || '').toUpperCase();
    if (ap === 'PM' && h < 12) h += 12;
    if (ap === 'AM' && h === 12) h = 0;
    return pad(h) + ':' + min;
  }

  function timestampFrom(date, time) {
    if (!date) return 0;
    const d = date.split('-').map(Number);
    const t = (time || '00:00').split(':').map(Number);
    return Math.floor(Date.UTC(d[0], d[1] - 1, d[2], t[0] || 0, t[1] || 0, 0) / 1000);
  }

  function time12(value) {
    if (!value) return '';
    const parts = value.split(':').map(Number);
    let h = parts[0] || 0;
    const m = pad(parts[1] || 0);
    const ap = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return pad(h) + ':' + m + ' ' + ap;
  }

  function EventDetailsPanel() {
    const postType = useSelect(function (select) {
      return select('core/editor').getCurrentPostType();
    }, []);

    const meta = useSelect(function (select) {
      return select('core/editor').getEditedPostAttribute('meta') || {};
    }, []);

    const editor = useDispatch('core/editor');
    if (postType !== 'em_event') return null;

    const startDate = dateFromTimestamp(meta.em_start_date_time || meta.em_start_date);
    const endDate = dateFromTimestamp(meta.em_end_date_time || meta.em_end_date) || startDate;
    const startTime = time24(meta.em_start_time || '');
    const endTime = time24(meta.em_end_time || '');

    function patch(values) {
      editor.editPost({ meta: Object.assign({}, meta, values) });
    }

    function updateStartDate(value) {
      patch({
        em_start_date_time: timestampFrom(value, startTime),
        em_start_date: timestampFrom(value, '00:00')
      });
    }

    function updateEndDate(value) {
      patch({
        em_end_date_time: timestampFrom(value, endTime),
        em_end_date: timestampFrom(value, '00:00')
      });
    }

    function updateStartTime(value) {
      patch({
        em_start_time: time12(value),
        em_start_date_time: timestampFrom(startDate, value)
      });
    }

    function updateEndTime(value) {
      patch({
        em_end_time: time12(value),
        em_end_date_time: timestampFrom(endDate || startDate, value)
      });
    }

    return el(
      PluginDocumentSettingPanel,
      { name: 'asosyoloji-weekly-event-details', title: 'Etkinlik Bilgileri' },
      el(TextControl, { label: 'Başlangıç tarihi', type: 'date', value: startDate, onChange: updateStartDate }),
      el(TextControl, { label: 'Başlangıç saati', type: 'time', value: startTime, onChange: updateStartTime }),
      el(TextControl, { label: 'Bitiş tarihi', type: 'date', value: endDate, onChange: updateEndDate }),
      el(TextControl, { label: 'Bitiş saati', type: 'time', value: endTime, onChange: updateEndTime }),
      el(ToggleControl, {
        label: 'Tüm gün etkinliği',
        checked: !!meta.em_all_day,
        onChange: function (v) { patch({ em_all_day: !!v }); }
      }),
      el(TextControl, {
        label: 'Fiyat / bilet bilgisi',
        value: meta.em_fixed_event_price || '',
        onChange: function (v) { patch({ em_fixed_event_price: v }); }
      }),
      el('p', { className: 'components-base-control__help' }, 'Etkinlik türü ve mekan için belge ayarlarındaki Etkinlik Türleri ve Mekanlar alanlarını kullanın.')
    );
  }

  wp.plugins.registerPlugin('asosyoloji-weekly-event-panel', { render: EventDetailsPanel });
})(window.wp);
