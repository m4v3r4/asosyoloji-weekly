(function (wp) {
  if (!wp || !wp.plugins || !wp.editPost || !wp.components || !wp.data || !wp.element) {
    return;
  }

  const el = wp.element.createElement;
  const PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
  const TextControl = wp.components.TextControl;
  const ToggleControl = wp.components.ToggleControl;
  const useSelect = wp.data.useSelect;
  const useDispatch = wp.data.useDispatch;

  function EventDetailsPanel() {
    const postType = useSelect(function (select) {
      return select('core/editor').getCurrentPostType();
    }, []);

    const meta = useSelect(function (select) {
      return select('core/editor').getEditedPostAttribute('meta') || {};
    }, []);

    const editor = useDispatch('core/editor');

    if (postType !== 'asosyoloji_event') {
      return null;
    }

    function setMeta(key, value) {
      const next = Object.assign({}, meta);
      next[key] = value;
      editor.editPost({ meta: next });
    }

    return el(
      PluginDocumentSettingPanel,
      {
        name: 'asosyoloji-weekly-event-details',
        title: 'Etkinlik Bilgileri',
        className: 'asosyoloji-weekly-event-panel'
      },
      el(TextControl, {
        label: 'Başlangıç tarihi',
        type: 'date',
        value: meta._aso_event_start_date || '',
        onChange: function (v) { setMeta('_aso_event_start_date', v); }
      }),
      el(TextControl, {
        label: 'Başlangıç saati',
        type: 'time',
        value: meta._aso_event_start_time || '',
        onChange: function (v) { setMeta('_aso_event_start_time', v); }
      }),
      el(TextControl, {
        label: 'Bitiş tarihi',
        type: 'date',
        value: meta._aso_event_end_date || '',
        onChange: function (v) { setMeta('_aso_event_end_date', v); }
      }),
      el(TextControl, {
        label: 'Bitiş saati',
        type: 'time',
        value: meta._aso_event_end_time || '',
        onChange: function (v) { setMeta('_aso_event_end_time', v); }
      }),
      el(TextControl, {
        label: 'Mekan',
        value: meta._aso_event_venue || '',
        onChange: function (v) { setMeta('_aso_event_venue', v); }
      }),
      el(TextControl, {
        label: 'Şehir',
        value: meta._aso_event_city || '',
        onChange: function (v) { setMeta('_aso_event_city', v); }
      }),
      el(TextControl, {
        label: 'Organizatör',
        value: meta._aso_event_organizer || '',
        onChange: function (v) { setMeta('_aso_event_organizer', v); }
      }),
      el(TextControl, {
        label: 'Etkinlik bağlantısı',
        type: 'url',
        value: meta._aso_event_url || '',
        onChange: function (v) { setMeta('_aso_event_url', v); }
      }),
      el(TextControl, {
        label: 'Fiyat / bilet bilgisi',
        value: meta._aso_event_price || '',
        onChange: function (v) { setMeta('_aso_event_price', v); }
      }),
      el(ToggleControl, {
        label: 'Ücretsiz etkinlik',
        checked: !!meta._aso_event_free,
        onChange: function (v) { setMeta('_aso_event_free', !!v); }
      })
    );
  }

  wp.plugins.registerPlugin('asosyoloji-weekly-event-panel', {
    render: EventDetailsPanel
  });
})(window.wp);
