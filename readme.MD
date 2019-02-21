KeyValue is a simple trait that can be used with Eloquent models or classes with specific constants to provide a key => value collection of all elements.

When using with non-Eloquent classes, the trait is looking for constants defined as
FIELD1_ID and FIELD1_LABEL, and so. If they exist, a ::getKeyValueCollection() call lists all FIELD_LABEL values keyed by the accompanying FIELD_ID. For example if we have this on a class:

const YES_ID = 1;
const YES_LABEL = 'Yes';
const NO_ID = 1;
const NO_LABEL = 'Yes';

then the result will be a collection of [1 => 'Yes', 2 => 'No']

There are some constants one can set on the class to change the way the trait works:

bool const USE_LABEL_AS_TRANSLATION_KEY: whether or not the label field should be sent through Laravel's __ function.

string const LABEL_FIELD: only on Eloquent models, this constant sets the label field (which is 'name' by default). If we need to use mutators (like a name_and_email field), this is where we set them up

string const ORDER_BY_FIELD: only on Eloquent models, this constant controls the field the results are ordered by.

string const ORDER_BY_DIRECTION: only on Eloquent models, this constant controls the direction the results are ordered in.
