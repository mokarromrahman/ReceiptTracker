package com.example.receipttracker;

import androidx.appcompat.app.AppCompatActivity;

import android.content.Intent;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.OutputStreamWriter;
import java.lang.ref.WeakReference;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;

import javax.net.ssl.HttpsURLConnection;

public class LoginPage extends AppCompatActivity {

    TextView status;
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login_page);

        //Initialize the components
        final EditText etEmail = (EditText)findViewById(R.id._etEmail);
        final EditText epPass = (EditText)findViewById(R.id._etPassword);
        Button btnLogin = (Button)findViewById(R.id._btnLogin);
        Button btnSignUp = (Button)findViewById(R.id._btnRegister);
        status = (TextView)findViewById(R.id._tvStatus);
        //Set starting message.
        status.setText("Enter your credentials to log in.");

        //Log in when the login button is clicked
        btnLogin.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                //Don't do anything if the email and password are not given
                if(etEmail.getText().length() < 1 || epPass.getText().length() < 1)
                    return;

                //If we're here we can try to log in
                AttemptLogin attemptLogin= new AttemptLogin(LoginPage.this);
                attemptLogin.execute(etEmail.getText().toString(),epPass.getText().toString());
            }
        });

        btnSignUp.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                startActivity(new Intent(LoginPage.this, SignUpPage.class));
            }
        });
    }

    private static class AttemptLogin extends AsyncTask<String, String, JSONObject>
    {
        //used this as a guide https://www.journaldev.com/12607/android-login-registration-php-mysql#android-login-registration-app
        //https://camposha.info/android-php-mysql-save-http-post-httpurlconnection/
        //https://stackoverflow.com/questions/9767952/how-to-add-parameters-to-httpurlconnection-using-post-using-namevaluepair
        //https://stackoverflow.com/questions/4205980/java-sending-http-parameters-via-post-method-easily
        //https://stackoverflow.com/questions/40574892/how-to-send-post-request-with-x-www-form-urlencoded-body
        //https://prodevsblog.com/view/android-httpurlconnection-post-and-get-request-tutorial/

        //JSON object to be passed to the other pages
        //private JSONObject returnedObject;
        //Weak reference to the login page
        private WeakReference<LoginPage> loginPageWeakReference;

        public AttemptLogin(LoginPage loginPage)
        {
            //Assign the weak reference variable to the login page.
            loginPageWeakReference = new WeakReference<LoginPage>(loginPage);
        }

        @Override
        protected void onPreExecute() {
            super.onPreExecute();

            //check that the login page is null or finishing
            LoginPage loginPage = loginPageWeakReference.get();
            if(loginPage == null || loginPage.isFinishing())
                return;

            loginPage.status.setText("Logging in.");
        }



        @Override
        protected JSONObject doInBackground(String... strings)
        {
            //JSONobject which will be filled with the data from the server.
            JSONObject data = new JSONObject();

            try {
                //create the parameters string to log in
                String urlParameters = "submit=Login&" + "email=" + strings[0] + "&password=" + strings[1];
                byte[] postData = urlParameters.getBytes(StandardCharsets.UTF_8);

                //URL and HTTP POST connection to the server.
                URL url = new URL("http://www.mokarrom.com/ReceiptWebservice/androidLogin.php");
                HttpURLConnection urlConnection = (HttpURLConnection)url.openConnection();
                urlConnection.setRequestMethod("POST");
                urlConnection.setReadTimeout(20000);
                urlConnection.setConnectTimeout(20000);
                urlConnection.setDoInput(true);
                urlConnection.setDoOutput(true);
                urlConnection.setRequestProperty( "Content-Type", "application/x-www-form-urlencoded");
                urlConnection.setRequestProperty( "charset", "utf-8");
                urlConnection.setRequestProperty( "Content-Length", Integer.toString( postData.length ));
                urlConnection.setUseCaches( false );

                //Try to send the data and read the response
                try {
                    //Send the data with an output stream
                    OutputStream os = urlConnection.getOutputStream();
                    BufferedWriter bw = new BufferedWriter(new OutputStreamWriter(os, "UTF-8"));
                    bw.write(urlParameters);
                    //flush the writer and close it and the output stream
                    bw.flush();
                    bw.close();
                    os.close();

                    if(urlConnection.getResponseCode() == HttpsURLConnection.HTTP_OK) {
                        BufferedReader reader = new BufferedReader(new InputStreamReader(urlConnection.getInputStream()));
                        StringBuilder stringBuilder = new StringBuilder();
                        String line;
                        while ((line = reader.readLine()) != null) {
                            stringBuilder.append(line);
                        }
                        //Parse the data from the API using JSON
                        data = new JSONObject(stringBuilder.toString()); // Here you have the data that you need
                        //String myToken = data.getString("token");
                        //Log.d("Token found = ", myToken);

                        //Assign the found jason object the class JSONObject
                        //returnedObject = data;
                    }
                }
                finally {
                    urlConnection.disconnect();
                    return data;
                }
            }
            catch (Exception e) {
                Log.e("Something went wrong", e.toString());
            }

            return null;
        }

        @Override
        protected void onPostExecute(JSONObject jsonObject) {
            super.onPostExecute(jsonObject);

            try
            {
                //check that the login page is null or finishing
                LoginPage loginPage = loginPageWeakReference.get();

                if(loginPage == null || loginPage.isFinishing())
                    return;

                if(jsonObject.getBoolean("found")) {

                    if(jsonObject.getBoolean("correctPass")) {
                        loginPage.status.setText("You have been logged in.");

                        //Go to main menu landing page of the App with a new User object
                        //which is created using the JSONObject.
                        User loggedInUser = new User(jsonObject);
                        Intent mainLandingPageIntent = new Intent(loginPage.getApplicationContext(), MainActivity.class);
                        //When sending this way, the intent is null in the next page.
                        //This was fixed by placing the intent in the onCreate method of the next activity.
                        mainLandingPageIntent.putExtra("Logged In User", loggedInUser);

                        //Start the next activity
                        loginPage.startActivity(mainLandingPageIntent);
                        //loginPage.startActivity(new Intent(loginPage.getApplicationContext(), MainActivity.class));
                    }
                    else
                    {
                        loginPage.status.setText("You have entered and incorrect password. Please try again.");
                    }
                }
                else
                {
                    loginPage.status.setText("There does not seem to be any users associated with this email. Please click the Sign Up button to register your account.");
                    //Log.d("User not found", "not found");
                }
            } catch (JSONException e) {
                e.printStackTrace();
            }
        }
    }

}
