(function (blocks, element, components, blockEditor, i18n) {
  const el = element.createElement;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const TextControl = components.TextControl;
  const SelectControl = components.SelectControl;
  const RangeControl = components.RangeControl;
  const ToggleControl = components.ToggleControl;
  const __ = i18n.__;

  blocks.registerBlockType('asosyoloji-weekly/list', {
    apiVersion: 3,
    title: __('Asosyoloji: Haftalık Etkinlikler', 'asosyoloji-weekly'),
    icon: 'calendar-alt',
    category: 'widgets',
    attributes: {
      title: { type: 'string', default: 'Haftalık' },
      mode: { type: 'string', default: 'week' },
      count: { type: 'number', default: 10 },
      city: { type: 'string', default: '' },
      compact: { type: 'boolean', default: false }
    },
    edit: function (props) {
      const a = props.attributes;
      return el(
        element.Fragment,
        {},
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            { title: __('Etkinlik listesi', 'asosyoloji-weekly'), initialOpen: true },
            el(TextControl, {
              label: __('Başlık', 'asosyoloji-weekly'),
              value: a.title || '',
              onChange: function (value) { props.setAttributes({ title: value }); }
            }),
            el(SelectControl, {
              label: __('Gösterim', 'asosyoloji-weekly'),
              value: a.mode || 'week',
              options: [
                { label: __('Bu hafta', 'asosyoloji-weekly'), value: 'week' },
                { label: __('Yaklaşan etkinlikler', 'asosyoloji-weekly'), value: 'upcoming' }
              ],
              onChange: function (value) { props.setAttributes({ mode: value }); }
            }),
            el(RangeControl, {
              label: __('Etkinlik sayısı', 'asosyoloji-weekly'),
              value: a.count || 10,
              min: 1,
              max: 30,
              onChange: function (value) { props.setAttributes({ count: value || 10 }); }
            }),
            el(TextControl, {
              label: __('Şehir filtresi', 'asosyoloji-weekly'),
              value: a.city || '',
              onChange: function (value) { props.setAttributes({ city: value }); }
            }),
            el(ToggleControl, {
              label: __('Kompakt görünüm', 'asosyoloji-weekly'),
              checked: !!a.compact,
              onChange: function (value) { props.setAttributes({ compact: value }); }
            })
          )
        ),
        el(
          'div',
          { className: 'aso-weekly-block-preview' },
          el('strong', {}, a.title || __('Haftalık', 'asosyoloji-weekly')),
          el('p', {}, a.mode === 'upcoming' ? __('Yaklaşan etkinlikler dinamik olarak gösterilecek.', 'asosyoloji-weekly') : __('Bu haftanın etkinlikleri dinamik olarak gösterilecek.', 'asosyoloji-weekly'))
        )
      );
    },
    save: function () { return null; }
  });

  blocks.registerBlockType('asosyoloji-weekly/calendar', {
    apiVersion: 3,
    title: __('Asosyoloji: Etkinlik Takvimi', 'asosyoloji-weekly'),
    icon: 'calendar',
    category: 'widgets',
    attributes: {
      title: { type: 'string', default: 'Etkinlik Takvimi' },
      year: { type: 'number', default: 0 },
      month: { type: 'number', default: 0 }
    },
    edit: function (props) {
      const a = props.attributes;
      return el(
        element.Fragment,
        {},
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            { title: __('Takvim ayarları', 'asosyoloji-weekly'), initialOpen: true },
            el(TextControl, {
              label: __('Başlık', 'asosyoloji-weekly'),
              value: a.title || '',
              onChange: function (value) { props.setAttributes({ title: value }); }
            }),
            el(RangeControl, {
              label: __('Ay', 'asosyoloji-weekly'),
              value: a.month || 0,
              min: 0,
              max: 12,
              help: __('0 = güncel ay', 'asosyoloji-weekly'),
              onChange: function (value) { props.setAttributes({ month: value || 0 }); }
            }),
            el(TextControl, {
              label: __('Yıl', 'asosyoloji-weekly'),
              type: 'number',
              value: a.year || '',
              help: __('Boş veya 0 = güncel yıl', 'asosyoloji-weekly'),
              onChange: function (value) { props.setAttributes({ year: parseInt(value, 10) || 0 }); }
            })
          )
        ),
        el(
          'div',
          { className: 'aso-weekly-block-preview' },
          el('strong', {}, a.title || __('Etkinlik Takvimi', 'asosyoloji-weekly')),
          el('p', {}, __('Aylık etkinlik takvimi ön yüzde dinamik olarak oluşturulur.', 'asosyoloji-weekly'))
        )
      );
    },
    save: function () { return null; }
  });
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.i18n);
