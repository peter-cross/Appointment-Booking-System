<style>

    body {
        background: transparent;
    }
    
    .container {
        padding: 0;
        max-width: none;
    }
    
    input[type="radio"], input[type="checkbox"] {
        margin: 0;
    }
    
    table div>input:hover, table div>input:focus, table div>input:visited {
        cursor: pointer;
    }

    table div>input[type='radio'] {
        opacity: 0;
    }

    table div>input[type='radio']:checked {
        opacity: 1;
        margin-top: -2px;
        outline: 0 !important;
    } 
    
    .btn-prev {
        height: 3.6em; 
        text-align: center; 
        font-size: 1.5em;
        //margin-left: 0.3em; 
        padding: 1px; 
        margin-right: 5px;
        position: absolute;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        border-color: transparent;
        border: none;
        background: transparent;
        outline: 0 !important;
    }

    .btn-prev:active {
        outline: 0 !important;
    }

    .btn-next {
        height: 3.6em; 
        text-align: center; 
        font-size: 1.5em;
        //margin-right: 0.3em; 
        padding: 1px; 
        margin-left: 5px;
        position: absolute;
        top: 50%;
        right: 0;
        transform: translateY(-50%);
        border-color: transparent;
        border: none;
        background: transparent;
        outline: 0 !important;
    }

    .btn-next:active {
        outline: 0 !important;
    }

    .with {
        text-align: center; 
        margin-top: -10px; 
        margin-bottom: 7px;
    }

    .specialist {
        height: 1.8em; 
        outline: 0; 
        border:none; 
        border-bottom: 1px solid #CCC; 
        background: #F3F3F3;
    }

    .schedule-table {
        margin: 0 1em; 
        padding: 0;
        width: auto;
    }
    
    .content-form {
        margin: 0px; 
        padding: 0 18px; 
        overflow-y: hidden; 
        text-align: center;    
    }
    
    .content-table {
        border-color: #CCC;
        width: 100%;
    }
    
    .content-columns-header {
        width:15em; 
        text-align: center; 
        font-size: 0.85em; 
        padding: 2px 0; 
        background: #FFF;
    }
    
    .content-rows-header {
        text-align: center; 
        margin: 0; 
        padding: 0 2px; 
        font-size: 0.75em; 
        width:12em; 
        background: #FFF;
    }
    
    .unavaiable-slot {
        margin: 0; 
        padding: 0; 
        background-color: #EEE;
        background-image: url('img/lightgrey.png');
        height: 1.5em;  
    }
    
    .booked-slot {
        margin: 0; 
        padding: 0; 
        background-color: #EEE;
        background-image: url('img/lightgrey.png');
        height: 1.5em; 
    }
    
    .available-slot {
        margin: 0; 
        padding: 0; 
        background-color: #9FF;
        background-image: url('img/lightgreen.png');
        height: 1.5em; 
    }
    
    .available-slot-div {
        margin: 0; 
        padding: 0;
    }
    
    .available-slot-input {
        padding: 0; 
        margin: 0; 
        width: 100%; 
        vertical-align: middle;
    }
    
    .input-table {
        margin: 5px 1em;
        background: transparent;
        padding: 0;
        width: 100%;
    }
    
    .input-table-name-cell {
        width: 21%; 
        text-align: left; 
        padding-left: 0; 
        font-size: 0.9em;    
    }
    
    .input-table-name-input {
        width:100%; 
        background: #FFF; 
        border: 1px solid #AAA; 
        box-shadow: 0 0 3px #888 inset;
    }
    
    .input-table-email-cell {
        width: 21%; 
        text-align: left; 
        padding-left: 0.6%; 
        font-size: 0.9em;
    }
    
    .input-table-email-input {
        width:100%; 
        background: #FFF; 
        border: 1px solid #AAA;    
        box-shadow: 0 0 3px #888 inset;
    }
    
    .input-table-time-cell {
        width: 10%; 
        text-align: left; 
        font-size: 0.9em;
    }
    
    .input-table-time-select {
        background: #FFF; 
        border: 1px solid #AAA; 
        box-shadow: 0 0 3px #888 inset;
        height: 1.75em;
        width: 100%;
    }
    
    .input-table-note-cell {
        width: 47%; 
        height: 1em; 
        text-align: left; 
        font-size: 0.9em;
        padding-right: 2.2em;
    }
    
    .input-table-note-input {
        width:100%; 
        background: #FFF; 
        border: 1px solid #AAA; 
        box-shadow: 0 0 3px #888 inset;
    }
    
    .input-table-submit-cell {
        width:10em; 
        vertical-align: bottom;
    }
    
    .input-table-submit-button {
        font-size: 1em; 
        width:10em; 
        color: #FFF; 
        border: 1px solid #AAA; 
        box-shadow: 0 0 10px #FFF; 
        background: #226; 
        padding: 3px 10px;
        margin: 3px 1em;
        float: right;
    }
    
    .empty-cell {
        width: 0.5%;
    }
    
    .time_reqd {
        overflow-x: hidden;    
    }
    
    .schedule-div {
        text-align: center;
        margin-top: -10px;
        display: inline-block;
        width: 100%;
    }
    
    .submit-table {
        margin: 0.5em 1.3em;    
    }
    
    .legend {
        height:1.2em; 
        width: 4em;
        display: inline-block;
        margin: 0;
        vertical-align: top;
    }
    
    .available {
        width:2em; 
        height:1.1em; 
        background-color:#9FF; 
        background-image: url('img/lightgreen.png');
        border: 1px solid #CCC; 
        display: inline-block;
        vertical-align: text-bottom;
    }
    
    .not-available {
        width:2em; 
        height:1.1em; 
        background-color:#EEE; 
        background-image: url('img/lightgrey.png');
        border: 1px solid #CCC; 
        display: inline-block;
        vertical-align: text-bottom;
    }
    
    .available-time {
        height:1.2em; 
        padding: 0 5px 0 2px; 
        width: auto;
        display: inline-block;
        vertical-align: top;
    }
    
    .not-availbale-time {
        height:1.2em; 
        padding: 0 5px 0 2px; 
        width: auto;
        display: inline-block;
        vertical-align: top;
    }
    
    .submit-div {
        width: 40.1em;
    }
    
</style>