const Select = (props) => {
    return (
        <select className={props.className}>
            <option>1243</option>
            <option>12434</option>
        </select>
    )
}


const Text = (props) => {
    return (
        <input
            className={props.className}
            name={props.name}
            type={props.type}
            value={props.value || ''}
            onChange={props.onChange}
            placeholder={props.placeholder}
        />

    )
}

const Textarea = (props) => {
    return (
        <textarea
            className={props.className}
            name={props.name}
            type={props.type}
            onChange={props.onChange}
            placeholder={props.placeholder}
        >{props.value || ''}</textarea>

    )
}


const Switch = (props) => {

    let gen = (value, label) => {
        let field = <input type="radio"
                           name={props.name}
                           value={value}
                           checked={props.value == value}
                           onChange={props.onChange}
        />;
        if (props.inline == true) {
            return <label className="radio-inline" key={value}>{field}{label}</label>
        } else {
            return <div className="radio" key={value}><label>{field}{label}</label></div>
        }
    };
    return (
        <div>
            {Object.entries(props.options).map(([value, label]) => gen(value, label))}
        </div>
    )
}

const CheckboxGroup = (props) => {
    let values = props.value || [];


    let gen = (value, label) => {
        let field = <input type="checkbox"
                           name={props.name}
                           value={value}
                           checked={values.indexOf(value) > -1}
                           onChange={props.onChange}
        />;
        if (props.inline == true) {
            return <label className="checkbox-inline" key={value}> {field}{label}</label>
        } else {
            return <div className="checkbox" key={value}><label> {field}{label}</label></div>
        }
    };
    return (
        <div>
            {Object.entries(props.options).map(([value, label]) => gen(value, label))}
        </div>
    )
}

/*
 *
 * <div class="checkbox">
 <label>
 <input type="checkbox"> Check me out
 </label>
 </div>


 <div class="checkbox">
 <label>
 <input type="checkbox" value="">
 Option one is this and that&mdash;be sure to include why it's great
 </label>
 </div>
 <div class="checkbox disabled">
 <label>
 <input type="checkbox" value="" disabled>
 Option two is disabled
 </label>
 </div>

 <div class="radio">
 <label>
 <input type="radio" name="optionsRadios" id="optionsRadios1" value="option1" checked>
 Option one is this and that&mdash;be sure to include why it's great
 </label>
 </div>
 <div class="radio">
 <label>
 <input type="radio" name="optionsRadios" id="optionsRadios2" value="option2">
 Option two can be something else and selecting it will deselect option one
 </label>
 </div>
 <div class="radio disabled">
 <label>
 <input type="radio" name="optionsRadios" id="optionsRadios3" value="option3" disabled>
 Option three is disabled
 </label>
 </div>



 * */

export {Text, Select, Switch, CheckboxGroup, Textarea};

