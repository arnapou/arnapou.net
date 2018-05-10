Ext.data.Types.Tags = {
    convert: function(v, data) {
        return data.join(', ');
    },
    sortType: function(v) {
        return v;
    },
    type: 'Tags'
};

Ext.apply(Ext.util.Format, {
    kbtext: function(value) {
        value = value.replace(/&/g, '&amp;');
        value = value.replace(/</g, '&lt;');
        value = value.replace(/>/g, '&gt;');
        //value = value.replace(/ /g, '&nbsp;');
        value = value.replace(/\t/g, '&nbsp;&nbsp;&nbsp;&nbsp;');
        value = value.replace(/((https?|ftp):\/\/[^ \n'"]+)/gi, '<a href="$1" target="_blank">$1</a>');
        value = value.replace(/\[\[\[\s*((.|\r|\n|\t)*?)\s*\]\]\]\n?/g, '<pre class="kb">$1</pre>');
        value = value.replace(/\[p\[\s*((.|\r|\n|\t)*?)\s*\]p\]/gi, '<p class="kb">$1</p>');
        value = value.replace(/\[\[\s*((.|\r|\n|\t)*?)\s*\]\]/g, '<code class="kb">$1</code>');
        value = value.replace(/''([^']+)''/g, '<b>$1</b>');
        value = value.replace(/\n/g, '<br />');
        return value;
    },
    kbtitle: function(value) {
        value = '<b>'+value+'</b>';
        return value;
    }
});

Ext.onReady(function() {

    /*
    var editor = new Ext.ux.grid.RowEditor({
        saveText: 'Update'
    });
    */

    var proxy = new Ext.data.HttpProxy({
        api: {
            read : '?action=getitems',
            create : '?action=additem',
            update: '?action=moditem',
            destroy: '?action=delitem'
        }
    });

    var Item = Ext.data.Record.create([
        {name: 'id', type : 'int', readOnly: true},
        {name: 'created', type : 'date', readOnly: true},
        {name: 'modified', type : 'date', readOnly: true},
        'title',
        'text',
        {name: 'tags', type : 'Tags'},
    ]);


    // TAGS
    var tags = new Ext.data.JsonStore({
        url: '?action=gettags',
        storeId: 'tags',
        fields: ['tag']
    });
    
    var writer = new Ext.data.JsonWriter({
        encode: true,
        writeAllFields: true
    });

    // ITEMS
    var kbitems = new Ext.data.JsonStore({
        proxy: proxy,
        storeId: 'kbitems',
        idProperty: 'id',
        root: 'items',
        writer: writer,
        fields: Item,
        sortInfo: {field:'title', direction:'ASC'},
        listeners: {
            beforeload: function() {
                kbitems.setBaseParam('q', Ext.getCmp('q').getValue());
                var selected = Ext.getCmp('liste_tags').getSelectedRecords();
                var t = [];
                for(i=0; i<selected.length; i++) {
                    t.push(selected[i].get('tag'));
                }
                kbitems.setBaseParam('tags', t.join("\t"));
            }
        }
    });
    
    // VIEWPORT
    new Ext.Viewport({
        layout: 'border',
        items: [{
            region: 'west',
            collapsible: false,
            title: 'Knowledge Base',
            width: 250,
            items: [{
                xtype: 'panel',
                border: false,
                header: false,
                layout: 'column',
                items: [{
                    columnWidth: 1,
                    xtype: 'textfield',
                    fieldLabel: '',
                    id: 'q',
                    name: 'q'
                }, {
                    xtype: 'button',
                    text: 'Clear',
                    listeners: {
                        click: function() {
                            Ext.getCmp('q').setValue('');
                            kbitems.reload();
                        }
                    }
                }, {
                    xtype: 'button',
                    text: 'Search',
                    listeners: {
                        click: function() {
                            kbitems.reload();
                        }
                    }
                }]
            },{
                xtype: 'panel',
                border: false,
                header: false,
                layout: 'column',
                items: [{
                    columnWidth: 0.34,
                    xtype: 'button',
                    text: 'Refresh',
                    listeners: {
                        click: function(){
                            tags.reload();
                        }
                    }
                }, {
                    columnWidth: 0.33,
                    xtype: 'button',
                    text: 'All tags',
                    listeners: {
                        click: function(){
                            Ext.getCmp('liste_tags').selectRange(0, tags.getCount()-1);
                            kbitems.reload();
                        }
                    }
                }, {
                    columnWidth: 0.33,
                    xtype: 'button',
                    text: 'No tag',
                    listeners: {
                        click: function(){
                            Ext.getCmp('liste_tags').clearSelections();
                            kbitems.reload();
                        }
                    }
                }]
            },{
                xtype: 'listview',
                store: tags,
                id: 'liste_tags',
                simpleSelect: false,
                multiSelect: true,
                columns: [{
                    header: 'Tags',
                    dataIndex: 'tag'
                }],
                listeners: {
                    selectionchange: function(){
                        kbitems.reload();
                    }
                }
            }]
        }, {
            region: 'center',
            title: 'Datas',
            layout: 'fit',
            items: {
                xtype: 'editorgrid',
                border: false,
                store: kbitems,
                id: 'liste_items',
                loadingText: 'Loading ...',
                //plugins: [editor],
                clicksToEdit: 2,
                disableSelection: false,
                sm: new Ext.grid.RowSelectionModel({
                    multipleSelect: true
                }),
                tbar: [{
                    iconCls: 'icon-item-add',
                    text: 'Add',
                    handler: function(){
                        var e = new Item({
                            title: '',
                            text: '',
                            tags: ''
                        });
                        //editor.stopEditing();
                        Ext.getCmp('liste_items').stopEditing();
                        kbitems.insert(0, e);
                        Ext.getCmp('liste_items').getView().refresh();
                        Ext.getCmp('liste_items').getSelectionModel().selectRow(0);
                        //editor.startEditing(0);
                        Ext.getCmp('liste_items').startEditing(0);
                    }
                },{
                    //ref: '../removeBtn',
                    iconCls: 'icon-item-delete',
                    text: 'Remove',
                    //disabled: true,
                    handler: function(){
                        //editor.stopEditing();
                        Ext.getCmp('liste_items').stopEditing();
                        var s = Ext.getCmp('liste_items').getSelectionModel().getSelections();
                        for(var i = 0, r; r = s[i]; i++){
                            kbitems.remove(r);
                        }
                    }
                }],

                columns: [
                    new Ext.grid.RowNumberer()
                ,{
                    header: 'Created',
                    dataIndex: 'created',
                    width: 70,
                    sortable: true,
                    xtype: 'datecolumn',
                    format: 'd/m/Y',
                    hidden: true,
                    isCellEditable: false,
                    editor: {
                        xtype: 'datefield',
                        readOnly: true
                    }
                    //tpl: '{modified:date("d/m/Y")}'
                },{
                    header: 'Modified',
                    dataIndex: 'modified',
                    width: 70,
                    sortable: true,
                    xtype: 'datecolumn',
                    format: 'd/m/Y',
                    hidden: true,
                    isCellEditable: false,
                    editor: {
                        xtype: 'datefield',
                        readOnly: true
                    }
                    //tpl: '{modified:date("d/m/Y")}'
                },{
                    header: 'Title',
                    dataIndex: 'title',
                    width: 140,
                    sortable: true,
                    renderer: Ext.util.Format.kbtitle,
                    editor: {
                        xtype: 'textfield',
                        allowBlank: false
                    }
                    //tpl: '<b>{title}</b>'
                },{
                    header: 'Text',
                    dataIndex: 'text',
                    width: 600,
                    renderer: Ext.util.Format.kbtext,
                    editor: {
                        xtype: 'textarea',
                        height: 200
                    }
                    //tpl: '{text:kbtext}'
                },{
                    header: 'Tags',
                    dataIndex: 'tags',
                    sortable: true,
                    width: 100,
                    editor: {
                        xtype: 'textfield'
                    }
                }]
            }
        }]
    });

    tags.load({
    /*
        callback: function(){
            Ext.getCmp('liste_tags').selectRange(0, tags.getCount()-1);
        }
    */
    });
});
