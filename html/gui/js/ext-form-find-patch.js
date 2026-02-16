if (Ext.form.BasicForm) {
    Ext.form.BasicForm.prototype.findField = function (id) {
        var field = this.items.get(id);

        if (!Ext.isObject(field)) {
            //searches for the field corresponding to the given id. Used recursively for composite fields
            var findMatchingField = function (f) {
                if (f && f.isFormField) {
                    if (f.dataIndex == id || f.id == id || f.getName() == id) {
                        field = f;
                        return false;
                    } else if (f.isComposite) {
                        return f.items.each(findMatchingField);
                    } else if (f instanceof Ext.form.CheckboxGroup && f.rendered) {
                        return f.eachItem(findMatchingField);
                    }
                }
            };

            this.items.each(findMatchingField);
        }
        return field || null;
    };
}
